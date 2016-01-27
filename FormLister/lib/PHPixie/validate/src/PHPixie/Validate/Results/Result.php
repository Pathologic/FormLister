<?php

namespace PHPixie\Validate\Results;

abstract class Result
{
    protected $results;
    protected $errorBuilder;
    
    protected $errors = array();
    protected $fields = array();
    
    public function __construct($results, $errorBuilder)
    {
        $this->results      = $results;
        $this->errorBuilder = $errorBuilder;
    }
    
    public function field($field)
    {
        if(!array_key_exists($field, $this->fields)) {
            $result = $this->buildFieldResult($field);
            $this->fields[$field] = $result;
        }
        
        return $this->fields[$field];
    }
    
    public function fields()
    {
        return $this->fields;
    }
    
    public function invalidFields()
    {
        $invalidFields = array();
        
        foreach($this->fields() as $field => $result) {
            if(!$result->isValid()) {
                $invalidFields[$field] = $result;
            }
        }
        
        return $invalidFields;
    }
    
    public function isValid()
    {
        if(!empty($this->errors)) {
            return false;
        }
        
        return count($this->invalidFields()) === 0;
    }
    
    public function addEmptyValueError()
    {
        return $this->addError(
            $this->errorBuilder->emptyValue()
        );
    }
    
    public function addFilterError($filter, $arguments = array())
    {
        return $this->addError(
            $this->errorBuilder->filter($filter, $arguments)
        );
    }
    
    public function addMessageError($message)
    {
        return $this->addError(
            $this->errorBuilder->message($message)
        );
    }
    
    public function addCustomError($type, $stringValue = null)
    {
        return $this->addError(
            $this->errorBuilder->custom($type, $stringValue)
        );
    }
    
    public function addDataTypeError()
    {
        return $this->addError(
            $this->errorBuilder->dataType()
        );
    }
    
    public function addScalarTypeError()
    {
        return $this->addError(
            $this->errorBuilder->scalarType()
        );
    }
    
    public function addItemCountError($count, $minCount, $maxCount = null)
    {
        return $this->addError(
            $this->errorBuilder->itemCount($count, $minCount, $maxCount)
        );
    }
    
    public function addInvalidFieldsError($fields)
    {
        return $this->addError(
            $this->errorBuilder->invalidFields($fields)
        );
    }
    
    public function addError($error)
    {
        $this->errors[]= $error;
        return $this;
    }
    
    public function errors()
    {
        return $this->errors;
    }
    
    abstract public function getValue();
    abstract protected function buildFieldResult($field);
}
