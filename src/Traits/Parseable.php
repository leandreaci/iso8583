<?php


namespace Andromeda\ISO8583\Traits;

use Andromeda\ISO8583\Contracts\IsoMessageContract;
use Andromeda\ISO8583\Parser;

Trait Parseable
{
    protected function addMessage()
    {
        $this->parser->addMessage($this->message);
    }

    protected function setIso(IsoMessageContract $iso)
    {
        $this->parser = new Parser($iso->getIso());
        $this->addMessage();
    }

    public function getBit($bit)
    {
        return $this->parser->getBit($bit);
    }

    public function messageType()
    {
        return substr($this->message,0,4);
    }

    public function fill($message)
    {
        $this->message = $message;
    }
}