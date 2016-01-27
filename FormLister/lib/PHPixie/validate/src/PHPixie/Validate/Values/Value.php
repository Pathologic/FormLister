<?php

namespace PHPixie\Validate\Values;

abstract class Value
{
    protected $isRequired = false;
    protected $rules = array();
    
    public function required()
    {
        $this->isRequired = true;
    }
    
    public function callback($callback)
    {
        $rule = $this->ruleBuilder->callback($callback);
        $this->addRule($rule);
    }
    
    public function conditional($conditionCallback)
    {
        $rule = $this->getConditionalRule();
        $rule = $this->ruleBuilder->callback($callback);
        $this->addRule($rule);
    }
    
    public function validate($value, $result)
    {
        $isEmpty = in_array($value, array(null, ''), true);
        
        if($isEmpty) {
            if(!this->isRequired) {
                $result->emptyError();
            }
            return;
        }
        
        $this->validateValue($value, $result);
    }
    
    protected function validateValue($value, $result);
}