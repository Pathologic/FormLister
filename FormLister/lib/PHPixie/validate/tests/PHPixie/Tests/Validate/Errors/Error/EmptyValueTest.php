<?php

namespace PHPixie\Tests\Validate\Errors\Error;

/**
 * @coversDefaultClass \PHPixie\Validate\Errors\Error\EmptyValue
 */
class EmptyValueTest extends \PHPixie\Tests\Validate\Errors\ErrorTest
{
    protected $type = 'empty';
    
    protected function prepareAsString()
    {
        return "Value is empty";
    }
    
    protected function error()
    {
        return new \PHPixie\Validate\Errors\Error\EmptyValue();
    }
}
