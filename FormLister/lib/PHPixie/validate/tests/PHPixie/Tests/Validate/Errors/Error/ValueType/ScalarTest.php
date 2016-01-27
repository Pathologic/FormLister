<?php

namespace PHPixie\Tests\Validate\Errors\Error\ValueType;

/**
 * @coversDefaultClass \PHPixie\Validate\Errors\Error\ValueType\Scalar
 */
class ScalarTest extends \PHPixie\Tests\Validate\Errors\Error\ValueTypeTest
{
    protected $valueType = 'scalar';
    
    protected function prepareAsString()
    {
        return "Value is not scalar";
    }
    
    public function error()
    {
        return new \PHPixie\Validate\Errors\Error\ValueType\Scalar();
    }
}
