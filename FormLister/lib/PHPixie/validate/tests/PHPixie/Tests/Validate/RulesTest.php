<?php

namespace PHPixie\Tests\Validate;

/**
 * @coversDefaultClass \PHPixie\Validate\Rules
 */
class RulesTest extends \PHPixie\Test\Testcase
{
    protected $builder;
    
    protected $rules;
    
    protected $filters;

    public function setUp()
    {
        $this->builder = $this->quickMock('\PHPixie\Validate\Builder');
        $this->rules = new \PHPixie\Validate\Rules(
            $this->builder
        );
        
        $this->filters = $this->quickMock('\PHPixie\Validate\Filters');
        $this->method($this->builder, 'filters', $this->filters, array());
    }
    
    /**
     * @covers ::__construct
     * @covers ::<protected>
     */
    public function testConstruct()
    {
        
    }
    
    /**
     * @covers ::value
     * @covers ::<protected>
     */
    public function testValue()
    {
        $rule = $this->rules->value();
        $this->assertInstance($rule, '\PHPixie\Validate\Rules\Rule\Value', array(
            'ruleBuilder' => $this->rules
        ));
    }
    
    /**
     * @covers ::filter
     * @covers ::<protected>
     */
    public function testFilter()
    {
        $rule = $this->rules->filter();
        $this->assertInstance($rule, '\PHPixie\Validate\Rules\Rule\Filter', array(
            'filterBuilder' => $this->filters
        ));
    }
    
    /**
     * @covers ::document
     * @covers ::<protected>
     */
    public function testDocument()
    {
        $rule = $this->rules->document();
        $this->assertInstance($rule, '\PHPixie\Validate\Rules\Rule\Data\Document', array(
            'rules' => $this->rules
        ));
    }
    
    /**
     * @covers ::arrayOf
     * @covers ::<protected>
     */
    public function testArrayOf()
    {
        $rule = $this->rules->arrayOf();
        $this->assertInstance($rule, '\PHPixie\Validate\Rules\Rule\Data\ArrayOf', array(
            'rules' => $this->rules
        ));
    }
}
