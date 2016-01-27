<?php

namespace PHPixie\Tests\Validate\Rules\Rule\Structure;

/**
 * @coversDefaultClass \PHPixie\Validate\Rules\Rule\Structure\ArrayOf
 */
class ArrayOfTest extends \PHPixie\Tests\Validate\Rules\Rule\DataTest
{
    /**
     * @covers ::minCount
     * @covers ::maxCount
     * @covers ::<protected>
     */
    public function testMinMaxCount()
    {
        foreach(array('minCount', 'maxCount') as $method) {
            $getMethod = 'get'.ucfirst($method);

            $this->assertSame(null, $this->rule->$getMethod());
            $this->assertSame($this->rule, $this->rule->$method(5));
            $this->assertSame(5, $this->rule->$getMethod());
        }
    }

    /**
     * @covers ::setKeyRule
     * @covers ::keyRule
     * @covers ::setItemRule
     * @covers ::itemRule
     * @covers ::<protected>
     */
    public function testRules()
    {
        foreach(array('keyRule', 'itemRule') as $method) {
            $setMethod = 'set'.ucfirst($method);
            $rule = $this->getRule();

            $this->assertSame(null, $this->rule->$method());
            $this->assertSame($this->rule, $this->rule->$setMethod($rule));
            $this->assertSame($rule, $this->rule->$method());
        }
    }

    /**
     * @covers ::key
     * @covers ::keyValue
     * @covers ::item
     * @covers ::itemValue
     * @covers ::<protected>
     */
    public function testKeyValue()
    {
        foreach(array('key', 'item') as $type) {
            $this->fieldTest($type, false, false);
            $this->fieldTest($type, false, true);
            $this->fieldTest($type, true, false);
            $this->fieldTest($type, true, true);
        }
    }

    protected function fieldTest($type, $isAdd, $withCallback)
    {
        list($rule, $callback) = $this->prepareBuildValue($withCallback);

        if($isAdd) {
            $method = 'value'.ucfirst($type);
            $expect = $rule;
        }else{
            $method = $type;
            $expect = $this->rule;
        }

        $args = array();
        if($withCallback) {
            $args[]= $callback;
        }

        $result = call_user_func_array(array($this->rule, $method), $args);
        $this->assertSame($expect, $result);

        $getMethod = $type.'Rule';
        $this->assertSame($rule, $this->rule->$getMethod());
    }

    /**
     * @covers ::validateData
     * @covers ::<protected>
     */
    public function testValidateData()
    {
        $this->validateDataTest(null, null, 4, false, false);
        $this->validateDataTest(null, null, 4, true, false);
        $this->validateDataTest(null, null, 4, false, true);
        $this->validateDataTest(null, null, 4, true, true);
        
        for($i = 3; $i<7; $i++) {
            $this->validateDataTest(3, 6, $i, false, false);
        }
    }

    protected function validateDataTest($minCount, $maxCount, $count, $withKeyRule, $withItemRule)
    {
        $this->rule = $this->rule();
        $result = $this->getResultMock();
        $values = array();
        $resultAt = 1;
        
        $this->rule->minCount($minCount);
        $this->rule->maxCount($maxCount);

        if(
            $minCount !== null && $count < $minCount ||
            $maxCount !== null && $count > $maxCount
        ) {
            $this->method($result, 'addItemCountError', null, array($count, $min, $max), $resultAt++);
        }
        
        for($i = 0; $i < $count; $i++) {
            $values[]= 'value'.$i;
        }
        
        if($withKeyRule) {
            $keyRule = $this->getRule();
            $this->rule->setKeyRule($keyRule);
        }
        
        if($withItemRule) {
            $itemRule = $this->getRule();
            $this->rule->setItemRule($itemRule);
        }
        
        if($withKeyRule || $withItemRule) {
            foreach($values as $key => $value) {
                $itemResult = $this->getResultMock();
                $this->method($result, 'field', $itemResult, array($key), $resultAt++);
                
                if($withKeyRule) {
                    $this->method($keyRule, 'validate', null, array($key, $itemResult), $key);
                }
                
                if($withItemRule) {
                    $this->method($itemRule, 'validate', null, array($value, $itemResult), $key);
                }
            }
        }
        
        $this->method($result, 'getValue', $values, array(), 0);
        $this->rule->validate($result);
    }

    protected function rule()
    {
        return new \PHPixie\Validate\Rules\Rule\Data\ArrayOf(
            $this->rules
        );
    }
}
