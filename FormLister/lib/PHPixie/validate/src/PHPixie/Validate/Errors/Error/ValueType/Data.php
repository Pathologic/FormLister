<?php

namespace PHPixie\Validate\Errors\Error\ValueType;

class Data extends \PHPixie\Validate\Errors\Error\ValueType
{
    public function valueType()
    {
        return 'data';
    }
    
    public function asString()
    {
        return "Value is neither object nor array";
    }
}
