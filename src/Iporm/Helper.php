<?php

namespace Iporm;

class Helper
{
    private $validQueryTypes;
    private $validOperands;

    public function __construct() 
    {
        $this->validQueryTypes = ['delete','insert_into','select','update'];
        $this->validOperands = ['=', '!=', '<', '>', 'in', 'not in', 'or'];
    }

    public function getValidQueryTypes()
    {
        return $this->validQueryTypes;
    }

    public function getValidOperands()
    {
        return $this->validOperands;
    }

    public function validateOperand($operand)
    {
        $return = $operand;
        if(!in_array($operand, $this->validOperands))
        {
            $return = '=';
        }

        return $return;
    }

    public function isIterable($value)
    {
        return (is_array($value) && !empty($value));
    }
}