<?php

namespace PHPixie\Validate;

class Errors
{
    public function emptyValue()
    {
        return new Errors\Error\EmptyValue();
    }
    
    public function filter($name, $arguments = array())
    {
        return new Errors\Error\Filter($name, $arguments);
    }
    
    public function message($message)
    {
        return new Errors\Error\Message($message);
    }
    
    public function custom($customType, $stringValue = null)
    {
        return new Errors\Error\Custom($customType, $stringValue);
    }
    
    public function dataType()
    {
        return new Errors\Error\ValueType\Data();
    }
    
    public function scalarType()
    {
        return new Errors\Error\ValueType\Scalar();
    }
    
    public function invalidFields($fields)
    {
        return new Errors\Error\Data\InvalidFields($fields);
    }
    
    public function itemCount($count, $minCount, $maxCount = null)
    {
        return new Errors\Error\Data\ItemCount($count, $minCount, $maxCount);
    }
}
