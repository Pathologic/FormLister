<?php

namespace PHPixie\Tests\Validate\Rules\Rule\Structure;

/**
 * @coversDefaultClass \PHPixie\Validate\Rules\Rule\Structure\Document
 */
class DocumentTest extends \PHPixie\Tests\Validate\Rules\Rule\DataTest
{
    /**
     * @covers ::allowExtraFields
     * @covers ::extraFieldsAllowed
     * @covers ::<protected>
     */
    public function testAllowExtraFields()
    {
        $this->assertSame(false, $this->rule->extraFieldsAllowed());

        $this->assertSame($this->rule, $this->rule->allowExtraFields());
        $this->assertSame(true, $this->rule->extraFieldsAllowed());

        $this->assertSame($this->rule, $this->rule->allowExtraFields(false));
        $this->assertSame(false, $this->rule->extraFieldsAllowed());
    }

    /**
     * @covers ::setFieldRule
     * @covers ::fieldRule
     * @covers ::fieldRules
     * @covers ::<protected>
     */
    public function testFieldRules()
    {
        $rules = array();
        for($i=0; $i<2; $i++) {
            $field = 'pixie'.$i;
            $rule = $this->getRule();
            $rules[$field]= $rule;
            $result = $this->rule->setFieldRule($field, $rule);
            
            $this->assertSame($this->rule, $result);
            $this->assertSame($rule, $this->rule->fieldRule($field));
        }
        
        $this->assertSame($rules, $this->rule->fieldRules());
        $this->assertSame(null, $this->rule->fieldRule('trixie'));
    }

    /**
     * @covers ::field
     * @covers ::fieldValue
     * @covers ::<protected>
     */
    public function testField()
    {
        $this->fieldTest(false, false);
        $this->fieldTest(false, true);
        $this->fieldTest(true, false);
        $this->fieldTest(true, true);
    }

    protected function fieldTest($isAdd, $withCallback)
    {
        list($rule, $callback) = $this->prepareBuildValue($withCallback);

        if($isAdd) {
            $method = 'valueField';
            $expect = $rule;
        }else{
            $method = 'field';
            $expect = $this->rule;
        }

        $args = array();
        if($withCallback) {
            $args[]= $callback;
        }

        array_unshift($args, 'pixie');
        $result = call_user_func_array(array($this->rule, $method), $args);
        $this->assertSame($expect, $result);

        $rules = $this->rule->fieldRules();
        $this->assertSame($rule, end($rules));
        $this->assertSame('pixie', key($rules));
    }

    /**
     * @covers ::validateData
     * @covers ::<protected>
     */
    public function testValidateData()
    {
        $this->validateDataTest(false, false);
        $this->validateDataTest(true, false);
        $this->validateDataTest(false, true);
        $this->validateDataTest(true, true);
    }

    protected function validateDataTest($allowExtraFields = false, $withExtraFields = false)
    {
        $this->rule = $this->rule();
        $result = $this->getResultMock();
        $values = array();
        $resultAt = 1;

        $this->rule->allowExtraFields($allowExtraFields);
        $extraKeys = array('stella', 'blum');

        if($withExtraFields) {
            $values = array_fill_keys($extraKeys, 1);
        }else{
            $values = array();
        }

        if(!$allowExtraFields && $withExtraFields) {
            $this->method($result, 'addInvalidFieldsError', null, array($extraKeys), $resultAt++);
        }

        $rules  = array();
        foreach(array('fairy', 'pixie', 'trixie') as $name) {
            $rule = $this->getRule();
            $rules[$name] = $rule;
            $this->rule->setFieldRule($name, $rule);

            $fieldResult = $this->getResultMock();
            $this->method($result, 'field', $fieldResult, array($name), $resultAt++);
            if($name !== 'trixie') {
                $value = $name.'Value';
                $values[$name] = $value;
            }else{
                $value = null;
            }

            $this->method($rule, 'validate', null, array($value, $fieldResult), 0);
        }
        
        $this->method($result, 'getValue', $values, array(), 0);
        $this->rule->validate($result);
    }
    
    protected function rule()
    {
        return new \PHPixie\Validate\Rules\Rule\Data\Document(
            $this->rules
        );
    }
}
