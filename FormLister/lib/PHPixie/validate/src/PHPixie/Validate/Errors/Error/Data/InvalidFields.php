<?php

namespace PHPixie\Validate\Errors\Error\Data;

class InvalidFields extends \PHPixie\Validate\Errors\Error
{
    protected $fields;
    
    public function __construct($fields)
    {
        $this->fields = $fields;
    }
    
    public function type()
    {
        return 'invalidFields';
    }
    
    public function fields()
    {
        return $this->fields;
    }
    
    public function asString()
    {
        return 'Invalid Fields: '.implode(', ',$this->fields);
    }
}
