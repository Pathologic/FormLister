<?php

namespace PHPixie\Validate\Values;

class Result
{
    protected $values;
    protected $errorBuilder;
    
    protected $errors       = array();
    protected $fieldResults = array();
    
    public function __construct($values, $errorBuilder)
    {
        $this->values       = $values;
        $this->errorBuilder = $errorBuilder;
    }
    
    public function errors()
    {
        return $this->errors;
    }
    
    public function fieldResults()
    {
        return $this->fieldResults;
    }
    
    public function field($name)
    {
        if(!array_key_exists($name, $this->fieldResults)) {
            $this->fieldResults[$name] = $this->values->result();
        }
        
        return $this->fieldResults[$name];
    }
    
    public function setFieldResult($name, $result)
    {
        $this->fieldResults[$name] = $result;
    }
    
    public function invalidFieldResults()
    {
        $invalidResults = array();
        
        foreach($this->fieldResults() as $field => $result) {
            if(!$result->isValid()) {
                $invalidResults[$field] = $result;
            }
        }
        
        return $invalidResults;
    }
    
    public function isValid()
    {
        if(!empty($this->errors)) {
            return false;
        }
        
        return count($this->invalidFieldResults()) === 0;
    }
    
    public function addEmptyValueError()
    {
        return $this->addError(
            $this->errorBuilder->emptyValue()
        );
    }
    
    public function addFilterError($filterName)
    {
        return $this->addError(
            $this->errorBuilder->filter($filterName)
        );
    }
    
    public function addMessageError($message)
    {
        return $this->addError(
            $this->errorBuilder->message($message)
        );
    }
    
    public function addCustomError($customType, $stringValue = null)
    {
        return $this->addError(
            $this->errorBuilder->custom($customType, $stringValue)
        );
    }
       
    public function addArrayTypeError()
    {
        return $this->addError(
            $this->errorBuilder->arrayType()
        );
    }
    
    public function addScalarTypeError()
    {
        return $this->addError(
            $this->errorBuilder->scalarType()
        );
    }
    
    public function addError($error)
    {
        $this->errors[]= $error;
        return $this;
    }
    
    public function addIvalidKeysError($extraKeys){}
    public function addArrayCountError($a,$b,$c){}
}
