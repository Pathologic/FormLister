<?php

namespace PHPixie\Validate\Filters\Registry\Type;

class String extends \PHPixie\Validate\Filters\Registry\Implementation
{
    public function filters()
    {
        return array(
            'length',
            'minLength',
            'maxLength',
            'lengthBetween'
        );
    }
    
    public function length($value, $length)
    {
        return $this->getLength($value) === $length;
    }
    
    public function minLength($value, $minLength)
    {
        return $this->getLength($value) >= $minLength;
    }

    public function maxLength($value, $maxLength)
    {
        return $this->getLength($value) <= $maxLength;
    }

    public function lengthBetween($value, $minLength, $maxLength)
    {
        $length = $this->getLength($value);
        return ($length >= $minLength && $length <= $maxLength);
    }
    
    protected function getLength($string)
    {
        return strlen(utf8_decode($string));
    }
}