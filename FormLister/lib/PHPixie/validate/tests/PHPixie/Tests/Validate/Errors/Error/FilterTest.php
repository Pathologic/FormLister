<?php

namespace PHPixie\Tests\Validate\Errors\Error;

/**
 * @coversDefaultClass \PHPixie\Validate\Errors\Error\Filter
 */
class FilterTest extends \PHPixie\Tests\Validate\Errors\ErrorTest
{
    protected $type       = 'filter';
    protected $filter     = 'between';
    protected $parameters = array(4, 5);
    
    /**
     * @covers ::__construct
     * @covers ::<protected>
     */
    public function testNoParameters()
    {
        $error = new \PHPixie\Validate\Errors\Error\Filter('email');
        $this->assertSame(array(), $error->parameters());
    }
    
    /**
     * @covers ::filter
     * @covers ::<protected>
     */
    public function testFilter()
    {
        $this->assertSame($this->filter, $this->error->filter());
    }
    
    /**
     * @covers ::parameters
     * @covers ::<protected>
     */
    public function testParameters()
    {
        $this->assertSame($this->parameters, $this->error->parameters());
    }
    
    protected function prepareAsString()
    {
        return "Value did not pass filter 'between'";
    }
        
    protected function error()
    {
        return new \PHPixie\Validate\Errors\Error\Filter(
            $this->filter,
            $this->parameters
        );
    }
}
