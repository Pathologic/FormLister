<?php

namespace PHPixie\Tests\Validate\Filters;

/**
 * @coversDefaultClass \PHPixie\Validate\Filters\Filter
 */
class FilterTest extends \PHPixie\Test\Testcase
{
    protected $filters;
    protected $name       = 'pixie';
    protected $parameters = array('trixie');
    
    public function setUp()
    {
        $this->filters = $this->quickMock('\PHPixie\Validate\Filters');
        
        $this->filter = new \PHPixie\Validate\Filters\Filter(
            $this->filters,
            $this->name,
            $this->parameters
        );
    }
    
    /**
     * @covers ::__construct
     * @covers ::<protected>
     */
    public function testConstruct()
    {
    
    }
    
    /**
     * @covers ::name
     * @covers ::<protected>
     */
    public function testName()
    {
        $this->assertSame($this->name, $this->filter->name());
    }
    
    /**
     * @covers ::parameters
     * @covers ::<protected>
     */
    public function testParameters()
    {
        $this->assertSame($this->parameters, $this->filter->parameters());
    }
    
    /**
     * @covers ::check
     * @covers ::<protected>
     */
    public function testCheck()
    {
        foreach(array(true, false) as $result) {
            $with = array($this->name, 5, $this->parameters);
            $this->method($this->filters, 'callFilter', $result, $with, 0);
            $this->assertSame($result, $this->filter->check(5));
        }
    }

}
