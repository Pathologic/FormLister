<?php

namespace PHPixie\Validate\Filters\Registry\Type;

class Compare extends \PHPixie\Validate\Filters\Registry\Implementation
{
    public function filters()
    {
        return array(
            'min',
            'max',
            'greater',
            'less',
            'between',
            'equals',
            'in'
        );
    }
    
    public function min($value, $min)
    {
        return $value >= $min;
    }
    
    public function max($value, $max)
    {
        return $value <= $max;
    }
    
    public function greater($value, $min)
    {
        return $value > $min;
    }
    
    public function less($value, $max)
    {
        return $value < $max;
    }

    public function between($value, $min, $max)
    {
        return ($value >= $min && $value <= $max);
    }
    
    public function equals($value, $allowed)
    {
        return $value === $allowed;
    }
    
    public function in($value, $allowed)
    {
        return in_array($value, $allowed, true);
    }
}