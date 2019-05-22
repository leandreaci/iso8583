<?php


namespace Andromeda\ISO8583;


class Parser
{
    const VARIABLE_LENGTH = TRUE;
    const FIXED_LENGTH    = FALSE;

    private $dataElement = [];
    private $_data   = [];
    private $_valid  = [];
    private $_bitmap = '';
    private $_mti    = '';
    private $_iso    = '';

    /**
     * IsoParser constructor.
     * @param array $dataElement
     */
    public function __construct($dataElement = [])
    {
        $this->dataElement = $dataElement;
    }

    /**
     * Set message to iso translate
     *
     * @param string $message
     *
     */
    public function setMessage($message)
    {
        $this->addMessage($message);
    }

    /**
     * Set data element
     *
     * @param array $dataElement
     */
    public function setDataElement(array $dataElement)
    {
        $this->dataElement = $dataElement;
    }

    /**
     * return data element in correct format
     *
     * @param array $data_element
     * @param mixed $data
     * @return string
     */
    private function _packElement($data_element, $data)
    {
        $result = "";

        if ($this->isNumeric($data_element, $data))
        {
            $data = str_replace(".", "", $data);

            if ($this->isFixedLength($data_element))
            {
                $result = sprintf("%0". $data_element[1] ."s", $data);
            }
            else
            {
                if (strlen($data) <= $data_element[1])
                {
                    $result = sprintf("%0". strlen($data_element[1])."d", strlen($data)). $data;
                }
            }
        }

        if ($this->isAlphaNumeric($data_element, $data))
        {
            if ($this->isFixedLength($data_element))
            {
                $result = sprintf("% ". $data_element[1] ."s", $data);
            }
            else
            {
                if (strlen($data) <= $data_element[1])
                {
                    $result = sprintf("%0". strlen($data_element[1])."s", strlen($data)). $data;
                }
            }
        }

        //bit value
        if ($data_element[0] == 'b' && strlen($data)<=$data_element[1])
        {
            //fixed length
            if ($this->isFixedLength($data_element))
            {
                $tmp = sprintf("%0". $data_element[1] ."d", $data);

                while ($tmp!='')
                {
                    $result  .= base_convert(substr($tmp, 0, 4), 2, 16);
                    $tmp      = substr($tmp, 4, strlen($tmp)-4);
                }
            }
        }

        return $result;
    }

    /**
     * calculate bitmap from data element
     *
     * @return string
     */
    private function _calculateBitmap()
    {
        $tmp  = sprintf("%064d", 0);
        $tmp2 = sprintf("%064d", 0);
        foreach ($this->_data as $key =>$val)
        {
            if ($key<65)
            {
                $tmp[$key-1]   = 1;
            }
            else
            {
                $tmp[0]        = 1;
                $tmp2[$key-65] = 1;
            }
        }

        $result = "";
        if ($tmp[0] == 1)
        {
            while ($tmp2!='')
            {
                $result  .= base_convert(substr($tmp2, 0, 4), 2, 16);
                $tmp2     = substr($tmp2, 4, strlen($tmp2)-4);
            }
        }
        $main = "";
        while ($tmp!='')
        {
            $main  .= base_convert(substr($tmp, 0, 4), 2, 16);
            $tmp    = substr($tmp, 4, strlen($tmp)-4);
        }
        $this->_bitmap = strtoupper($main. $result);

        return $this->_bitmap;
    }


    /**
     * parse iso string and retrieve mti
     *
     * @return void
     */
    private function _parseMTI()
    {
        $this->addMTI(substr($this->_iso, 0, 4));

        if (strlen($this->_mti) == 4 && $this->_mti[1]!=0)
        {
            $this->_valid['mti'] = true;
        }

    }

    /**
     * clear all data
     *
     * @return void
     */
    private function _clear()
    {
        $this->_mti    = '';
        $this->_bitmap = '';
        $this->_data   = '';
        $this->_iso    = '';
    }

    /**
     * parse iso string and retrieve bitmap
     *
     * @return string
     */
    private function _parseBitmap()
    {
        $this->_valid['bitmap'] = false;
        $inp = substr($this->_iso, 4, 32);

        if (strlen($inp)>=16)
        {
            $primary    = '';
            $secondary  = '';

            //CONVERT TO BINARY
            for ($i = 0; $i<16; $i++)
            {
                $primary .= sprintf("%04d", base_convert($inp[$i], 16, 2));
            }

            if ($primary[0] == 1 && strlen($inp)>=32)
            {
                for ($i=16; $i<32; $i++)
                {
                    $secondary .= sprintf("%04d", base_convert($inp[$i], 16, 2));
                }
                $this->_valid['bitmap'] = true;
            }

            if ($secondary == '') $this->_valid['bitmap'] = true;
        }

        //save to data element with ? character
        $tmp    = $primary . $secondary;

        $this->_data = [];

        for ($i = 0; $i<strlen($tmp); $i++)
        {
            if ($tmp[$i] == 1)
            {
                $new = $i + 1;
                $this->_data[$new] = '?';
            }
        }

        $this->_bitmap = $tmp;

        return $tmp;
    }

    /**
     * parse iso string and retrieve data element
     *
     * @return array
     */
    private function _parseData()
    {
        if (isset($this->_data[1]) && $this->_data[1]  ==  '?')
        {
            $inp = substr($this->_iso, 4+32, strlen($this->_iso)-4-32);
        }
        else
        {
            $inp = substr($this->_iso, 4+16, strlen($this->_iso)-4-16);
        }

        if (is_array($this->_data))
        {
            $this->_valid['data'] = true;
            foreach ($this->_data as $key =>$val)
            {
                $this->_valid['de'][$key] = false;
                if ($this->dataElement[$key][0]!='b')
                {
                    //fixed length
                    if ($this->dataElement[$key][2] == self::FIXED_LENGTH)
                    {
                        $tmp = substr($inp, 0, $this->dataElement[$key][1]);
                        if (strlen($tmp) == $this->dataElement[$key][1])
                        {
                            if ($this->dataElement[$key][0] == 'n')
                            {
                                $this->_data[$key] = substr($inp, 0, $this->dataElement[$key][1]);
                            }
                            else
                            {
                                $this->_data[$key] = ltrim(substr($inp, 0, $this->dataElement[$key][1]));
                            }
                            $this->_valid['de'][$key] = true;
                            $inp                      = substr($inp, $this->dataElement[$key][1], strlen($inp)-$this->dataElement[$key][1]);
                        }
                    }
                    //dynamic length
                    else
                    {
                        $len = strlen($this->dataElement[$key][1]);
                        $tmp = substr($inp, 0, $len);
                        if (strlen($tmp) == $len )
                        {
                            $num = (integer) $tmp;
                            $inp = substr($inp, $len, strlen($inp)-$len);

                            $tmp2 = substr($inp, 0, $num);
                            if (strlen($tmp2) == $num)
                            {
                                if ($this->dataElement[$key][0] == 'n')
                                {
                                    $this->_data[$key] = (double) $tmp2;
                                }
                                else
                                {
                                    $this->_data[$key] = ltrim($tmp2);
                                }
                                $inp                      = substr($inp, $num, strlen($inp)-$num);
                                $this->_valid['de'][$key] = true;
                            }
                        }

                    }
                }
                else
                {
                    if ($key>1)
                    {
                        //fixed length
                        if ($this->dataElement[$key][2] == self::FIXED_LENGTH)
                        {
                            $start = false;
                            for ($i = 0; $i<$this->dataElement[$key][1]/4; $i++)
                            {
                                $bit = base_convert($inp[$i], 16, 2);

                                if ($bit!=0) $start = true;
                                if ($start) $this->_data[$key] .= $bit;
                            }
                            $this->_data[$key] = $bit;
                        }
                    }
                    else
                    {
                        $tmp = substr($this->_iso, 4+16, 16);
                        if (strlen($tmp) == 16)
                        {
                            $this->_data[$key]        = substr($this->_iso, 4+16, 16);
                            $this->_valid['de'][$key] = true;
                        }
                    }
                }
                if (!$this->_valid['de'][$key]) $this->_valid['data'] = false;
            }
        }

        return $this->_data;
    }

    /**
     * add data element
     *
     * @param int $bit
     * @param mixed $data
     * @return void
     */
    public function addData($bit, $data)
    {
        if ($bit<1 || $bit>128)
            throw new \Exception('addData invalid bit:'.$bit.':'.$data);

        $result = $this->_packElement($this->dataElement[$bit], $data);

        if (is_null($result))
            throw new \Exception('addData failure for bit:'.$bit);

        $this->_data[$bit] = $result;
        ksort($this->_data);
        $this->_calculateBitmap();
    }

    /**
     * add MTI
     *
     * @param string $mti
     * @return void
     */
    public function addMTI($mti)
    {
        if (strlen($mti) == 4 && ctype_digit($mti))
        {
            $this->_mti = $mti;
        }
    }


    /**
     * retrieve data element
     *
     * @return array
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * retrieve data element
     *
     * @return mixed
     */
    public function getBit($bit)
    {
        if($bit >= 1 && $bit <= 127)
        {
            if(is_array($this->_data) && array_key_exists($bit,$this->_data))
            {
                return $this->_data[$bit];
            }
        }

        return false;
    }

    /**
     * retrieve bitmap
     *
     * @return string
     */
    public function getBitmap()
    {
        return $this->_bitmap;
    }

    /**
     * retrieve mti
     *
     * @return string
     */
    public function getMTI()
    {
        return $this->_mti;
    }

    /**
     * retrieve iso with all complete data
     *
     * @return string
     */
    public function getISO()
    {
        $this->_iso = $this->_mti. $this->_bitmap. implode($this->_data);
        return $this->_iso;
    }

    /**
     * add ISO string
     *
     * @param string $iso
     * @return void
     */
    public function addMessage($iso)
    {
        $this->_clear();

        if ($iso)
        {
            $this->_iso = $iso;
            $this->_parseMTI();
            $this->_parseBitmap();
            $this->_parseData();
        }
    }

    /**
     * return true if iso string is a valid 8583 format or false if not
     *
     * @return bool
     */
    public function validateISO()
    {
        return $this->_valid['mti'] && $this->_valid['bitmap'] && $this->_valid['data'];
    }

    /**
     * remove existing data element
     *
     * @param int $bit
     * @return void
     */
    public function removeData($bit)
    {
        if ($bit>1 && $bit<129)
        {
            unset($this->_data[$bit]);
            ksort($this->_data);
            $this->_calculateBitmap();
        }
    }

    /**
     * redefine bit definition
     *
     * @param int $bit
     * @param string $type
     * @param int $length
     * @param bool $dynamic
     * @return void
     */
    public function redefineBit($bit, $type, $length, $dynamic)
    {
        if ($bit>1 && $bit<129)
        {
            $this->dataElement[$bit] = array($type, $length, $dynamic);
        }
    }

    /**
     * @param $data_element
     * @param $data
     * @return bool
     */
    private function isNumeric($data_element, $data): bool
    {
        return $data_element[0] == 'n' && is_numeric($data) && strlen($data) <= $data_element[1];
    }

    /**
     * @param $data_element
     * @return bool
     */
    private function isFixedLength($data_element): bool
    {
        return $data_element[2] == self::FIXED_LENGTH;
    }

    /**
     * @param $data_element
     * @param $data
     * @return bool
     */
    private function isAlphaNumeric($data_element, $data): bool
    {
        return ($data_element[0] == 'a' && ctype_alpha($data) && strlen($data) <= $data_element[1]) ||
            ($data_element[0] == 'an' && ctype_alnum($data) && strlen($data) <= $data_element[1]) ||
            ($data_element[0] == 'z' && strlen($data) <= $data_element[1]) ||
            ($data_element[0] == 'ans' && strlen($data) <= $data_element[1]);
    }

    private function descToHuman(array $dataElement, int $field): array
    {
        if(array_key_exists(0,$dataElement) && array_key_exists(1,$dataElement) && array_key_exists(2,$dataElement))
        {
            return [
                'field' => $field,
                'type' => $dataElement[0],
                'size' => $dataElement[1],
                'variable' => $dataElement[2],
                'completedChar' => $dataElement[0]  == 'ans' ? ' ' : '0'
            ];
        }

        return [];
    }

    public function getFieldDetails(int $field): array
    {
        return array_key_exists($field,$this->dataElement) ?  $this->descToHuman($this->dataElement[$field],$field) : [];
    }
}