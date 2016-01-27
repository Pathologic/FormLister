<?php

namespace PHPixie\Validate\Rules\Rule\Data;

class ArrayOf extends \PHPixie\Validate\Rules\Rule\Data
{
    protected $minCount = null;
    protected $maxCount = null;

    protected $keyRule;
    protected $itemRule;

    public function minCount($minCount)
    {
        $this->minCount = $minCount;
        return $this;
    }

    public function maxCount($maxCount)
    {
        $this->maxCount = $maxCount;
        return $this;
    }

    public function getMinCount()
    {
        return $this->minCount;
    }

    public function getMaxCount()
    {
        return $this->maxCount;
    }

    public function key($callback = null)
    {
        $this->valueKey($callback);
        return $this;
    }

    public function valueKey($callback = null)
    {
        $rule = $this->buildValue($callback);
        $this->setKeyRule($rule);
        return $rule;
    }

    public function setKeyRule($rule)
    {
        $this->keyRule = $rule;
        return $this;
    }

    public function keyRule()
    {
        return $this->keyRule;
    }

    public function item($callback = null)
    {
        $this->valueItem($callback);
        return $this;
    }

    public function valueItem($callback = null)
    {
        $rule = $this->buildValue($callback);
        $this->setItemRule($rule);
        return $rule;
    }

    public function setItemRule($rule)
    {
        $this->itemRule = $rule;
        return $this;
    }

    public function itemRule()
    {
        return $this->itemRule;
    }

    public function validateData($result, $value)
    {
        $count = count($value);
        $min = $this->minCount;
        $max = $this->maxCount;
        
        if($min !== null && $count < $min || $max !== null && $count > $max) {
            $result->addItemCountError($count, $min, $max);
        }
        
        if($this->itemRule === null && $this->keyRule === null) {
            return array();
        }
        
        foreach($value as $key => $item) {
            $itemResult = $result->field($key);

            if($this->keyRule !== null) {
                $this->keyRule->validate($key, $itemResult);
            }

            if($this->itemRule !== null) {
                $this->itemRule->validate($item, $itemResult);
            }
        }
    }
}
