<?php

namespace PHPixie\Tests\Validate\Results\Result\Root;

/**
 * @coversDefaultClass \PHPixie\Validate\Results\Result\Field
 */
class FieldTest extends \PHPixie\Tests\Validate\Results\ResultTest
{
    protected $rootResult;
    protected $path = 'pixie';
    
    public function setUp()
    {
        $this->rootResult = $this->quickMock('\PHPixie\Validate\Results\Result\Root');
        parent::setUp();
    }
    
    /**
     * @covers ::getValue
     * @covers ::<protected>
     */
    public function testGetValue()
    {
        $this->method($this->rootResult, 'getPathValue', 5, array($this->path), 0);
        $this->assertSame(5, $this->result->getValue());
    }
    
    /**
     * @covers ::path
     * @covers ::<protected>
     */
    public function testPath()
    {
        $this->assertSame($this->path, $this->result->path());
    }
    
    /**
     * @covers ::buildFieldResult
     * @covers ::<protected>
     */
    public function testBuildFieldResult()
    {
        $result = $this->getFieldResult();
        
        $with = array($this->rootResult, 'pixie.trixie');
        $this->method($this->results, 'field', $result, $with, 0);
        
        $this->assertSame($result, $this->result->field('trixie'));
    }
    
    protected function result()
    {
        return new \PHPixie\Validate\Results\Result\Field(
            $this->results,
            $this->errors,
            $this->rootResult,
            $this->path
        );
    }
    
    protected function resultMock($methods = null)
    {
        return $this->getMock(
            '\PHPixie\Validate\Results\Result\Field',
            $methods,
            array(
                $this->results,
                $this->errors,
                $this->rootResult,
                $this->path
            )
        );
    }
}
