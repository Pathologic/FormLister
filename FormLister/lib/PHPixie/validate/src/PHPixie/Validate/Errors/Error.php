<?php

namespace PHPixie\Validate\Errors;

abstract class Error
{
    public function __toString()
    {
        return $this->asString();
    }
    
    abstract public function type();
    abstract public function asString();
}