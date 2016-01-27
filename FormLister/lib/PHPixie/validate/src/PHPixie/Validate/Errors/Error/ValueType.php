<?php

namespace PHPixie\Validate\Errors\Error;

abstract class ValueType extends \PHPixie\Validate\Errors\Error
{
    public function type()
    {
        return 'valueType';
    }
    
    abstract public function valueType();
}
