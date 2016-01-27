<?php

namespace PHPixie\Validate\Errors\Error\ValueType;

class Scalar extends \PHPixie\Validate\Errors\Error\ValueType
{
    public function valueType()
    {
        return 'scalar';
    }
    
    public function asString()
    {
        return "Value is not scalar";
    }
}
