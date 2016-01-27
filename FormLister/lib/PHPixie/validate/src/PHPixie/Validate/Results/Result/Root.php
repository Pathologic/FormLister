<?php

namespace PHPixie\Validate\Results\Result;

class Root extends \PHPixie\Validate\Results\Result
{
    protected $value;
    
    public function __construct($results, $errorBuilder, $value)
    {
        parent::__construct($results, $errorBuilder);
        
        $this->value = $value;
    }
    
    public function getValue()
    {
        return $this->value;
    }
    
    public function getPathValue($path)
    {
        $path = explode('.', $path);
        $value = $this->value;
        
        foreach($path as $step) {
            if(is_array($value) && array_key_exists($step, $value)) {
                $value = $value[$step];
                continue;
            }
            
            if(is_object($value) && property_exists($value, $step)) {
                $value = $value->$step;
                continue;
            }
            
            return null;
        }
        
        return $value;
    }
    
    protected function buildFieldResult($path)
    {
        return $this->results->field($this, $path);
    }
}
