<?php

namespace PHPixie\Tests;

class ValidateCallback{
    public function __invoke()
    {
        
    }
}

/**
 * @coversDefaultClass \PHPixie\Validate
 */
class ValidateTest extends \PHPixie\Test\Testcase
{
    protected $validate;
    
    protected $builder;
    
    public function setUp()
    {
        $this->validate = $this->getMockBuilder('\PHPixie\Validate')
            ->setMethods(array('buildBuilder'))
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->builder = $this->quickMock('\PHPixie\Validate\Builder');
        $this->method($this->validate, 'buildBuilder', $this->builder, array(), 0);
        
        $this->validate->__construct();
    }
    
    /**
     * @covers ::__construct
     * @covers ::<protected>
     */
    public function testConstructor()
    {
        
    }
    
    /**
     * @covers ::buildBuilder
     * @covers ::<protected>
     */
    public function testBuildBuilder()
    {
        $this->validate = new \PHPixie\Validate();
        
        $builder = $this->validate->builder();
        $this->assertInstance($builder, '\PHPixie\Validate\Builder');
    }
    
    /**
     * @covers ::builder
     * @covers ::<protected>
     */
    public function testBuilder()
    {
        $this->assertSame($this->builder, $this->validate->builder());
    }
    
    /**
     * @covers ::rules
     * @covers ::<protected>
     */
    public function testRules()
    {
        $rules = $this->prepareRules();
        $this->assertSame($rules, $this->validate->rules());
    }
    
    /**
     * @covers ::validator
     * @covers ::<protected>
     */
    public function testValidator()
    {
        $rule = $this->quickMock('\PHPixie\Validate\Rules\Rule');
        $validator = $this->prepareValidator($rule);
        
        $this->assertSame($validator, $this->validate->validator($rule));
    }
    
    /**
     * @covers ::documentValidator
     * @covers ::<protected>
     */
    public function testDocumentValidator()
    {
        $this->validatorTest('document', false);
        $this->validatorTest('document', true);
    }
    
    /**
     * @covers ::arrayValidator
     * @covers ::<protected>
     */
    public function testArrayValidator()
    {
        $this->validatorTest('array', false);
        $this->validatorTest('array', true);
    }
    
    protected function validatorTest($type, $withCallback)
    {
        $rules = $this->prepareRules();
        
        $ruleType = $type == 'document' ? 'document' : 'arrayOf';
        
        $class = '\PHPixie\Validate\Rules\Rule\Data\\'.ucfirst($ruleType);
        $rule = $this->quickMock($class);
        
        $this->method($rules, $ruleType, $rule, array(), 0);
        
        $validator = $this->prepareValidator($rule, 1);
        
        $args = array();
        if($withCallback) {
            $callback = $this->quickMock('\PHPixie\Tests\ValidateCallback');
            $this->method($callback, '__invoke', null, array($rule), 0);
            $args[]= $callback;
        }
        
        $method = $type == 'document' ? 'document' : 'array';
        $method.= 'Validator';
        
        $result = call_user_func_array(array($this->validate, $method), $args);
        $this->assertSame($validator, $result);
    }
    
    protected function prepareRules($at = 0)
    {
        $rules = $this->quickMock('\PHPixie\Validate\Rules');
        $this->method($this->builder, 'rules', $rules, array(), $at);
        return $rules;
    }
    
    protected function prepareValidator($rule, $at = 0)
    {
        $validator = $this->quickMock('\PHPixie\Validate\Validator');
        $this->method($this->builder, 'validator', $validator, array($rule), $at);
        return $validator;
    }
}
