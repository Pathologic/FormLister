<?php

namespace PHPixie\Validate\Errors\Error;

class Filter extends \PHPixie\Validate\Errors\Error
{
    protected $filter;
    protected $parameters;
    
    public function __construct($filter, $parameters = array())
    {
        $this->filter     = $filter;
        $this->parameters = $parameters;
    }
    
    public function type()
    {
        return 'filter';
    }
    
    public function filter()
    {
        return $this->filter;
    }
    
    public function parameters()
    {
        return $this->parameters;
    }
    
    public function asString()
    {
        return "Value did not pass filter '{$this->filter}'";
    }
}
