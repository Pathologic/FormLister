<?php

namespace PHPixie\Tests\Validate\Errors\Error;

/**
 * @coversDefaultClass \PHPixie\Validate\Errors\Error\Custom
 */ 
class CustomTest extends \PHPixie\Tests\Validate\Errors\ErrorTest
{
    protected $type        = 'custom';
    protected $customType  = 'fairy';
    protected $stringValue = 'pixie';
    
    protected function prepareAsString()
    {
        return $this->stringValue;
    }
    
    /**
     * @covers ::customType
     * @covers ::<protected>
     */
    public function testCustomType()
    {
        $this->assertSame($this->customType, $this->error->customType());
    }
    
    /**
     * @covers ::asString
     * @covers ::<protected>
     */
    public function testAsStringNoValue()
    {
        $this->error = new \PHPixie\Validate\Errors\Error\Custom(
            $this->customType
        );
        
        $this->assertSame($this->customType, $this->error->asString());
    }
    
    protected function error()
    {
        return new \PHPixie\Validate\Errors\Error\Custom(
            $this->customType,
            $this->stringValue
        );
    }
}