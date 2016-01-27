<?php

namespace PHPixie\Validate;

class Results
{
    protected $builder;
    
    public function __construct($builder)
    {
        $this->builder = $builder;
    }
    
    public function root($value)
    {
        return new Results\Result\Root(
            $this,
            $this->builder->errors(),
            $value
        );
    }
    
    public function field($root, $path)
    {
        return new Results\Result\Field(
            $this,
            $this->builder->errors(),
            $root,
            $path
        );
    }
}
