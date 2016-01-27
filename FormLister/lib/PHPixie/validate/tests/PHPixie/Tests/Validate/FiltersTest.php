<?php

namespace PHPixie\Tests\Validate;

/**
 * @coversDefaultClass \PHPixie\Validate\Filters
 */ 
class FiltersTest extends \PHPixie\Test\Testcase
{
    protected $externalRegistries;
    
    protected $filters;
    
    public function setUp()
    {
        $this->externalRegistries = array(
            $this->getRegistry(),
            $this->getRegistry()
        );
        
        $this->filters = $this->getMock(
            '\PHPixie\Validate\Filters',
            array('buildRegistries'),
            array($this->externalRegistries)
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
     * @covers ::registries
     * @covers ::<protected>
     */
    public function testRegistries()
    {
        $registries = $this->prepareRequireRegistries();
        for($i=0; $i<2; $i++) {
            $this->assertSame($registries, $this->filters->registries());
        }
    }
    
    /**
     * @covers ::callFilter
     * @covers ::<protected>
     */
    public function testCallFilter()
    {
        $filterMap = $this->prepareRequireFilterMap();
        $at = 1;
        
        foreach($filterMap as $registry) {
            $filters = $filterMap[$registry];
            foreach($filters as $key => $filter) {
                $this->method($registry, 'callFilter', true, array($filter, 'pixie', array(5)), $at);
                $this->assertSame(true, $this->filters->callFilter($filter, 'pixie', array(5)));
                $at = 0;
            }
        }
        
        $filters = $this->filters;
        $this->assertException(function() use($filters) {
            $filters->callFilter('trixie', 5, array());
        }, '\PHPixie\Validate\Exception');
    }
    
    /**
     * @covers ::buildRegistries
     * @covers ::<protected>
     */
    public function testBuildRegistries()
    {
        $this->filters = new \PHPixie\Validate\Filters($this->externalRegistries);
        $registries = $this->filters->registries();
        
        $classes = array(
            '\PHPixie\Validate\Filters\Registry\Type\Compare',
            '\PHPixie\Validate\Filters\Registry\Type\Pattern',
            '\PHPixie\Validate\Filters\Registry\Type\String'
        );
        
        foreach($classes as $key => $class) {
            $this->assertInstance($registries[$key], $class);
        }
    }
    
    /**
     * @covers ::filter
     * @covers ::<protected>
     */
    public function testFilter()
    {
        $filter = $this->filters->filter('pixie', array(5));
        $this->assertInstance($filter, '\PHPixie\Validate\Filters\Filter', array(
            'filters'    => $this->filters,
            'name'       => 'pixie',
            'parameters' => array(5)
        ));
        
        $filter = $this->filters->filter('pixie');
        $this->assertInstance($filter, '\PHPixie\Validate\Filters\Filter', array(
            'filters'    => $this->filters,
            'name'       => 'pixie',
            'parameters' => array()
        ));
    }
    
    protected function prepareRequireFilterMap()
    {
        $map = new \SplObjectStorage();
        
        $registries = $this->prepareRequireRegistries();
        foreach($registries as $key => $registry) {
            $filters = array('pixie'.$key, 'trixie'.$key);
            $this->method($registry, 'filters', $filters, array(), 0);
            $map[$registry] = $filters;
        }
        
        return $map;
    }
    
    protected function prepareRequireRegistries()
    {
        $registries = array(
            $this->getRegistry(),
            $this->getRegistry()
        );
        
        $this->method($this->filters, 'buildRegistries', $registries, array(), 0);
        return array_merge($registries, $this->externalRegistries);
    }
    
    protected function getRegistry()
    {
        return $this->quickMock('\PHPixie\Validate\Filters\Registry');
    }
}