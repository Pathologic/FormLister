<?php

namespace PHPixie\Tests\Validate\Rules\Rule;

/**
 * @coversDefaultClass \PHPixie\Validate\Rules\Rule\Callback
 */
class CallbackTest extends \PHPixie\Tests\Validate\Rules\RuleTest
{
    protected $callback;
    protected $rule;
    
    public function setUp()
    {
        $this->callback = $this->callbackMock();
        $this->rule = new \PHPixie\Validate\Rules\Rule\Callback(
            $this->callback
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
     * @covers ::validate
     * @covers ::<protected>
     */
    public function testValidate()
    {
        $result = $this->getResultMock();
        $this->method($result, 'getValue', 5, array(), 0);
        
        $this->method($this->callback, '__invoke', null, array($result, 5), 0);
        $this->rule->validate($result);
    }
}
