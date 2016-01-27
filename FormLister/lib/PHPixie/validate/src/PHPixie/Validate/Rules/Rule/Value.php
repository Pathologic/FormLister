<?php

namespace PHPixie\Validate\Rules\Rule;

class Value implements \PHPixie\Validate\Rules\Rule
{
    protected $ruleBuilder;
    
    protected $isRequired = false;
    protected $rules      = array();
    
    public function __construct($ruleBuilder)
    {
        $this->ruleBuilder = $ruleBuilder;
    }
    
    public function required($isRequired = true)
    {
        $this->isRequired = $isRequired;
        return $this;
    }
    
    public function isRequired()
    {
        return $this->isRequired;
    }
    
    public function arrayOf($callback = null)
    {
        $this->addArrayOf($callback);
        return $this;
    }
    
    public function addArrayOf($callback = null)
    {
        $rule = $this->ruleBuilder->arrayOf();
        if($callback !== null) {
            $callback($rule);
        }
        
        $this->addRule($rule);
        return $rule;
    }
    
    public function document($callback = null)
    {
        $this->addDocument($callback);
        return $this;
    }
    
    public function addDocument($callback = null)
    {
        $rule = $this->ruleBuilder->document();
        if($callback !== null) {
            $callback($rule);
        }
        
        $this->addRule($rule);
        return $rule;
    }
    
    public function filter($parameter = null)
    {
        $this->addFilter($parameter);
        return $this;
    }
    
    public function addFilter($parameter = null)
    {
        $rule = $this->ruleBuilder->filter();
        $this->applyFilterParameter($rule, $parameter);
        
        $this->addRule($rule);
        return $rule;
    }
    
    public function callback($callback)
    {
        $rule = $this->ruleBuilder->callback($callback);
        return $this->addRule($rule);
    }
    
    protected function applyFilterParameter($filterRule, $parameter)
    {
        if($parameter === null) {
            return;
        }
        
        if(is_string($parameter)) {
            $filterRule->filter($parameter);
            
        }elseif(is_callable($parameter)) {
            $parameter($filterRule);
            
        }elseif(is_array($parameter)) {
            $filterRule->filters($parameter);
            
        }else{
            $type = gettype($parameter);
            throw new \PHPixie\Validate\Exception("Invalid filter definition '$type'");
        }
    }
    
    public function addRule($rule)
    {
        $this->rules[] = $rule;
        return $this;
    }
    
    public function rules()
    {
        return $this->rules;
    }
    
    public function validate($value, $result)
    {
        $isEmpty = in_array($value, array(null, ''), true);
        
        if($isEmpty) {
            if($this->isRequired) {
                $result->addEmptyValueError();
            }
            return;
        }
        
        foreach($this->rules() as $rule) {
            $rule->validate($value, $result);
        }
    }
}
