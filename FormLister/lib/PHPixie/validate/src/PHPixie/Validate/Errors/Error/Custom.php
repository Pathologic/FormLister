<?php

namespace PHPixie\Validate\Errors\Error;

class Custom extends \PHPixie\Validate\Errors\Error
{
    protected $count;
    protected $stringValue;
    
    public function __construct($customType, $stringValue = null)
    {
        $this->customType  = $customType;
        $this->stringValue = $stringValue;
    }
    
    public function type()
    {
        return 'custom';
    }
    
    public function customType()
    {
        return $this->customType;
    }
    
    public function asString()
    {
        if($this->stringValue === null) {
            return $this->customType;
        }
        
        return $this->stringValue;
    }
}
