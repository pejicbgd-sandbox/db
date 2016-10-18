<?php

namespace Iporm;

class InvalidTypeException extends Exception
{
    private $value;

    private $type;

    public function __construct($value, $type)
    {
        $this->value = $value;
        $this->type = $type;
    }

    public function getMessageText()
    {
        return "Value [{$this->value}] cannot be of type [$this->type]!";
    }
}