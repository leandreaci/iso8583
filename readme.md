<p align="center">
    <img src="http://andromedadev.com/assets/img/marca.png">
</p>

<p align="center">
    <b>Version: 0.2</b> 
</p>

# ISO8583
This package can generate and parse messages in the ISO8583 standard.

## Installation

```
composer require leandroandreaci/iso8583
```

## Using

  - Each new instance of the parser requires a class containing the iso message definitions.

```php
<?php


namespace Andromeda\ISO8583;

use Andromeda\ISO8583\Contracts\IsoMessageContract;

class ExampleMessage implements IsoMessageContract
{
    public function getIso()
    {
        return [
            1   => ['b',   32,  self::FIXED_LENGTH],
            2   => ['ans',  99,  self::VARIABLE_LENGTH],
            3   => ['n',   6,   self::FIXED_LENGTH],
            4   => ['n',   12,  self::FIXED_LENGTH],
            5   => ['n',   12,  self::FIXED_LENGTH],
         ];
     }
}
```
- Example to read a ISO8583 using a ExampleMessage in package.
```php
$messageDefinition = new Andromeda\ISO8583\ExampleMessage();
$parser = new Andromeda\ISO8583\Parser($messageDefinition);

//Example 01 
$parser->addMessage('0800...');
$parser->validateISO(); //return false

//Example 02
$parser->addMessage('0800A238000000C0000804000000000000000000010401000443495194000443040176007008043177567000140003001000');
$parser->validateISO(); //return true
$parser->getBit('07');  //return 0401000443
```

- Example to make a ISO8583 using a ExampleMessage in package.
```php

$message = new \Andromeda\ISO8583\ExampleMessage();
$isoMaker = new \Andromeda\ISO8583\Parser($message);
$isoMaker->addMTI('0800');
$isoMaker->addData(3, '123456');
$isoMaker->addData(4, '000000001000');
$isoMaker->addData(7, '1234567890');
$isoMaker->getISO(); // return 080032000000000000001234560000000010001234567890

```