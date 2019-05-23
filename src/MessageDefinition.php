<?php


namespace Andromeda\ISO8583;


abstract class MessageDefinition
{
    const VARIABLE_LENGTH = TRUE;
    const FIXED_LENGTH    = FALSE;

    abstract public function getIso(): array;
}