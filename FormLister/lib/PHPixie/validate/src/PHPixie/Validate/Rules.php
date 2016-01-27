<?php

namespace PHPixie\Validate;

class Rules
{
    protected $builder;
    
    public function __construct($builder)
    {
        $this->builder = $builder;
    }
    
    public function callback($callback)
    {
        return new Rules\Rule\Callback($callback);
    }
    
    public function value()
    {
        return new Rules\Rule\Value($this);
    }
    
    public function filter()
    {
        return new Rules\Rule\Filter(
            $this->builder->filters()
        );
    }
    
    public function document()
    {
        return new Rules\Rule\Data\Document($this);
    }
    
    public function arrayOf()
    {
        return new Rules\Rule\Data\ArrayOf($this);
    }
}
