<?php

namespace PHPixie\Tests\Validate;

/**
 * @coversDefaultClass \PHPixie\Validate\Validator
 */
class ValidatorTest extends \PHPixie\Test\Testcase
{
    protected $results;
    protected $rule;
    
    public function setUp()
    {
        $this->results = $this->quickMock('\PHPixie\Validate\Results');
        $this->rule    = $this->quickMock('\PHPixie\Validate\Rules\Rule');
        
        $this->validator = new \PHPixie\Validate\Validator(
            $this->results,
            $this->rule
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
     * @covers ::rule
     * @covers ::<protected>
     */
    public function tetsRule()
    {
        $this->assertSame($this->rule, $this->validator->rule());
    }
    
    /**
     * @covers ::validate
     * @covers ::<protected>
     */
    public function testValidate()
    {
        $result = $this->quickMock('\PHPixie\Validate\Results\Result\Root');
        $this->method($this->results, 'root', $result, array(5), 0);
        
        $this->method($this->rule, 'validate', null, array($result), 0);
        
        $this->assertSame($result, $this->validator->validate(5));
    }
}
