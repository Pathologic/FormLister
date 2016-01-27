<?php

namespace PHPixie\Validate\Errors\Error;

class EmptyValue extends \PHPixie\Validate\Errors\Error
{
    public function type()
    {
        return 'empty';
    }
    
    public function asString()
    {
        return "Value is empty";
    }
}
