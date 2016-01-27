<?php

namespace PHPixie\Tests\Validate\Errors\Error;

/**
 * @coversDefaultClass \PHPixie\Validate\Errors\Error\ValueType
 */
abstract class ValueTypeTest extends \PHPixie\Tests\Validate\Errors\ErrorTest
{
    protected $type = 'valueType';
    protected $valueType;
    
    /**
     * @covers ::valueType
     * @covers ::<protected>
     */
    public function testValueType()
    {
        $this->assertSame($this->valueType, $this->error->valueType());
    }
}
