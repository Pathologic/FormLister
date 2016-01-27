<?php

namespace PHPixie\Tests\Validate\Rules;


class Callback{
    public function __invoke()
    {
        
    }
}

abstract class RuleTest extends \PHPixie\Test\Testcase
{
    protected function ruleCallback($rule)
    {
        $callback = $this->callbackMock();
        $this->method($callback, '__invoke', null, array($rule), 0);
        return $callback;
    }
    
    protected function callbackMock()
    {
        return $this->quickMock('\PHPixie\Tests\Validate\Rules\Callback');
    }
    
    protected function getResultMock()
    {
        return $this->abstractMock('\PHPixie\Validate\Results\Result');
    }
    
    protected function getRule()
    {
        return $this->quickMock('\PHPixie\Validate\Rules\Rule');
    }
}
