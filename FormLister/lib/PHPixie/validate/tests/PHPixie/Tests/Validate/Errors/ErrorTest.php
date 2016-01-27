<?php

namespace PHPixie\Tests\Validate\Errors;

/**
 * @coversDefaultClass \PHPixie\Validate\Errors\Error
 */ 
abstract class ErrorTest extends \PHPixie\Test\Testcase
{
    protected $error;
    
    protected $type;
    
    public function setUp()
    {
        $this->error = $this->error();
    }
    
    
    /**
     * @covers ::type
     * @covers ::<protected>
     */
    public function testType()
    {
        $this->assertSame($this->type, $this->error->type());
    }
    
    /**
     * @covers ::asString
     * @covers ::<protected>
     */
    public function testAsString()
    {
        $string = $this->prepareAsString();
        $this->assertSame($string, $this->error->asString());
    }
    
    /**
     * @covers ::toString
     * @covers ::<protected>
     */
    public function testToString()
    {
        $string = $this->prepareAsString();
        $this->assertSame($string, (string) $this->error);
    }
    
    abstract protected function prepareAsString();
    abstract protected function error();
}