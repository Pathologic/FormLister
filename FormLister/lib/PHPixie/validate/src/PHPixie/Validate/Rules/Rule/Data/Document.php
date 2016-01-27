<?php

namespace PHPixie\Validate\Rules\Rule\Data;

class Document extends \PHPixie\Validate\Rules\Rule\Data
{
    protected $fieldRules = array();
    protected $allowExtraFields = false;

    public function allowExtraFields($allowExtraFields = true)
    {
        $this->allowExtraFields = $allowExtraFields;
        return $this;
    }

    public function extraFieldsAllowed()
    {
        return $this->allowExtraFields;
    }

    public function field($field, $callback = null)
    {
        $this->valueField($field, $callback);
        return $this;
    }

    public function valueField($field, $callback = null)
    {
        $rule = $this->buildValue($callback);
        $this->setFieldRule($field, $rule);
        return $rule;
    }

    public function setFieldRule($field, $rule)
    {
        $this->fieldRules[$field] = $rule;
        return $this;
    }

    public function fieldRules()
    {
        return $this->fieldRules;
    }

    public function fieldRule($field)
    {
        if(!array_key_exists($field, $this->fieldRules)) {
            return null;
        }
        
        return $this->fieldRules[$field];
    }

    protected function validateData($result, $value)
    {
        if(!$this->allowExtraFields) {
            $extraFields = array_diff(
                array_keys($value),
                array_keys($this->fieldRules)
            );

            if(!empty($extraFields)) {
                $result->addInvalidFieldsError($extraFields);
            }
        }

        foreach($this->fieldRules as $field => $rule) {
            if(array_key_exists($field, $value)) {
                $fieldValue = $value[$field];

            }else{
                $fieldValue = null;
            }

            $fieldResult = $result->field($field);
            $rule->validate($fieldValue, $fieldResult);
        }
    }
}
