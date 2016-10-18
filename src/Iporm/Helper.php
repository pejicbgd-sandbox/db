<?php

namespace Iporm;

use InvalidTypeException;

class Helper
{
    public function __construct() 
    {
        
    }

    public function validateScalar($value, $type)
    {
        switch($type) {
            case 'string':
                if(!filter_var($value, FILTER_SANITIZE_STRING)) {
                    throw new InvalidTypeException($value, $type);
                }
                break;
        }
    }

    public function isIterable($array)
    {
        return (is_array($array) && count($array));
    }
}