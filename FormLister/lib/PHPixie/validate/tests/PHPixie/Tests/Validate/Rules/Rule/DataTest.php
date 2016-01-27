<?php

namespace PHPixie\Tests\Validate\Rules\Rule;

/**
 * @coversDefaultClass \PHPixie\Validate\Rules\Rule\Data
 */
abstract class DataTest extends \PHPixie\Tests\Validate\Rules\RuleTest
{
    protected $rules;
    protected $rule;

    public function setUp()
    {
        $this->rules = $this->quickMock('\PHPixie\Validate\Rules');
        $this->rule = $this->rule();
    }

    /**
     * @covers ::__construct
     * @covers ::<protected>
     */
    public function testConstruct()
    {

    }

    /**
     * @covers ::validate
     * @covers ::<protected>
     */
    public function testValidateNotArray()
    {
        $result = $this->getResultMock();
        $this->method($result, 'getValue', 5, array(), 0);
        $this->method($result, 'addDataTypeError', null, array(), 1);
        $this->rule->validate($result);
    }

    protected function prepareBuildValue($withCallback = false, $rulesAt = 0)
    {
        $rule = $this->prepareValueRule($rulesAt);

        $callback = null;
        if($withCallback) {
            $callback = $this->ruleCallback($rule);
        }

        return array($rule, $callback);
    }

    protected function prepareValueRule($rulesAt = 0)
    {
        $rule = $this->quickMock('\PHPixie\Validate\Rules\Rule\Value');
        $this->method($this->rules, 'value', $rule, array(), $rulesAt);
        return $rule;
    }

    abstract protected function rule();
}
