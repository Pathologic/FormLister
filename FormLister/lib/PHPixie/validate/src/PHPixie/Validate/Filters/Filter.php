<?php

namespace PHPixie\Validate\Filters;

class Filter
{
    protected $filters;
    protected $name;
    protected $parameters;
    
    public function __construct($filters, $name, $parameters = array())
    {
        $this->filters   = $filters;
        $this->name      = $name;
        $this->parameters = $parameters;
    }
    
    public function name()
    {
        return $this->name;
    }
    
    public function parameters()
    {
        return $this->parameters;
    }
    
    public function check($value)
    {
        return $this->filters->callFilter(
            $this->name,
            $value,
            $this->parameters
        );
    }
}
