<?php

namespace PHPixie\Tests\Validate\Rules\Rule;

/**
 * @coversDefaultClass \PHPixie\Validate\Rules\Rule\Filter
 */
class FilterTest extends \PHPixie\Tests\Validate\Rules\RuleTest
{
    protected $filters;
    
    protected $rule;
    
    public function setUp()
    {
        $this->filters = $this->quickMock('\PHPixie\Validate\Filters');
        
        $this->rule = new \PHPixie\Validate\Rules\Rule\Filter(
            $this->filters
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
     * @covers ::filter
     * @covers ::getFilters
     * @covers ::<protected>
     */
    public function testFilter()
    {
        $filters[]= $this->prepareAddFilter('pixie');
        $this->rule->filter('pixie');
        
        $filters[]= $this->prepareAddFilter('trixie', array(5));
        $this->rule->filter('trixie', array(5));
        
        $this->assertSame($filters, $this->rule->getFilters());
    }
    
    /**
     * @covers ::__call
     * @covers ::<protected>
     */
    public function testCall()
    {
        $filters[]= $this->prepareAddFilter('pixie');
        $this->rule->pixie();
        
        $filters[]= $this->prepareAddFilter('trixie', array(5));
        $this->rule->trixie(5);
        
        $this->assertSame($filters, $this->rule->getFilters());
    }
    
    /**
     * @covers ::filters
     * @covers ::<protected>
     */
    public function testFilters()
    {
        $filters = array();
        
        $filters[]= $this->prepareAddFilter('pixie');
        $filters[]= $this->prepareAddFilter('trixie', array(5), 1);
        
        $this->rule->filters(array(
            'pixie',
            'trixie' => array(5)
        ));
        
        $this->assertSame($filters, $this->rule->getFilters());
    }
    
    /**
     * @covers ::validate
     * @covers ::<protected>
     */
    public function testValidate()
    {
        $filters = array();
        for($i=0; $i<5; $i++) {
            $filter = $this->prepareAddFilter('pixie'.$i);
            $filters[]= $filter;
            
            $this->rule->filter('pixie'.$i);
        }
        
        $this->validateTest($filters, false, false);
        $this->validateTest($filters, true, false);
        $this->validateTest($filters, true, true);
    }
    
    protected function validateTest($filters, $isScalar, $isValid)
    {
        $result = $this->getResultMock();
        if(!$isScalar) {
            $value = array();
            $this->method($result, 'addScalarTypeError',null, array(), 1);
        }else{
            $value = 'trixie';
            foreach($filters as $key => $filter) {
                if(!$isValid && $key > 3) {
                    continue;
                }
                
                $filterValid = $isValid || $key !== 3;
                $this->method($filter, 'check', $filterValid, array($value), 0);
                
                if(!$filterValid) {
                    $this->method($filter, 'name', 'pixie', array(), 1);
                    $this->method($filter, 'parameters', array(2), array(), 2);
                    
                    $this->method($result, 'addFilterError', null, array('pixie', array(2)), 1);
                }
            }
        }
        
        $this->method($result, 'getValue', $value, array(), 0);
        $this->rule->validate($result);
    }
    
    protected function prepareAddFilter($name, $parameters = array(), $at = 0)
    {
        $filter = $this->quickMock('\PHPixie\Validate\Filters\Filter');
        $this->method($this->filters, 'filter', $filter, array($name, $parameters), $at);
        return $filter;
    }
}
