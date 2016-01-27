<?php

namespace PHPixie\Tests\Validate;

/**
 * @coversDefaultClass \PHPixie\Validate\Results
 */
class ResultsTest extends \PHPixie\Test\Testcase
{
    protected $builder;
    
    protected $results;
    
    protected $errors;

    public function setUp()
    {
        $this->builder = $this->quickMock('\PHPixie\Validate\Builder');
        $this->results = new \PHPixie\Validate\Results(
            $this->builder
        );
        
        $this->errors = $this->quickMock('\PHPixie\Validate\Errors');
        $this->method($this->builder, 'errors', $this->errors, array());
    }
    
    /**
     * @covers ::__construct
     * @covers ::<protected>
     */
    public function testConstruct()
    {
        
    }
    
    /**
     * @covers ::root
     * @covers ::<protected>
     */
    public function testRoot()
    {
        $result = $this->results->root(5);
        $this->assertInstance($result, '\PHPixie\Validate\Results\Result\Root', array(
            'value' => 5
        ));
    }
    
    /**
     * @covers ::field
     * @covers ::<protected>
     */
    public function testField()
    {
        $rootResult = $this->quickMock('\PHPixie\Validate\Results\Result\Root');
        
        $result = $this->results->field($rootResult, 'trixie');
        $this->assertInstance($result, '\PHPixie\Validate\Results\Result\Field', array(
            'rootResult' => $rootResult,
            'path'       => 'trixie'
        ));
    }
    
}
