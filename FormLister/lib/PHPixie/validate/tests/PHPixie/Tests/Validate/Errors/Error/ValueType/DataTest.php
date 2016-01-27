<?php

namespace PHPixie\Tests\Validate\Errors\Error\ValueType;

/**
 * @coversDefaultClass \PHPixie\Validate\Errors\Error\ValueType\Data
 */
class DataTest extends \PHPixie\Tests\Validate\Errors\Error\ValueTypeTest
{
    protected $valueType = 'data';
    
    protected function prepareAsString()
    {
        return "Value is neither object nor array";
    }
    
    public function error()
    {
        return new \PHPixie\Validate\Errors\Error\ValueType\Data();
    }
}
