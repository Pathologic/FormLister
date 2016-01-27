<?php

namespace PHPixie\Tests\Validate\Filters\Registry;

/**
 * @coversDefaultClass \PHPixie\Validate\Filters\Registry\Implementation
 */ 
abstract class ImplementationTest extends \PHPixie\Test\Testcase
{
    protected $sets = array();
    
    public function setUp()
    {
        $this->registry = $this->registry();
    }
    
    /**
     * @covers ::filters
     * @covers ::<protected>
     */
    public function testFilters()
    {
        $filters = array_keys($this->sets);
        $this->assertSame($filters, $this->registry->filters());
    }
    
    /**
     * @covers ::callFilter
     * @covers ::<protected>
     */     
    public function testCallInvalidFilter()
    {
        $registry = $this->registry;
        
        $this->assertException(function() use($registry) {
            $registry->callFilter('invalid', 5, array());
        }, '\PHPixie\Validate\Exception');
    }
    
    /**
     * @covers ::<public>
     * @covers ::<protected>
     */     
    public function testCallFilter()
    {
        foreach($this->sets as $name => $filterSets) {
            foreach($filterSets as $set) {
                $result = $this->registry->callFilter($name, $set[0], $set[1]);
                $this->assertSame($set[2], $result);
            }
        }
    }
    
    abstract protected function registry();
}