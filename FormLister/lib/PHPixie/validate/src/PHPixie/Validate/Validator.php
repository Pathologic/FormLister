<?php

namespace PHPixie\Validate;

class Validator
{
    protected $results;
    protected $rule;
    
    public function __construct($results, $rule)
    {
        $this->results = $results;
        $this->rule    = $rule;
    }
    
    public function rule()
    {
        return $this->rule;
    }
    
    public function validate($value)
    {
        $result = $this->results->root($value);
        $this->rule->validate($value, $result);
        return $result;
    }
}
