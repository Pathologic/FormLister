<?php

namespace PHPixie\Tests\Validate\Errors\Error\Data;

/**
 * @coversDefaultClass \PHPixie\Validate\Errors\Error\Data\InvalidFields
 */
class InvalidFieldsTest extends \PHPixie\Tests\Validate\Errors\ErrorTest
{
    protected $type   = 'invalidFields';
    protected $fields = array('pixie', 'trixie');
    
    /**
     * @covers ::fields
     * @covers ::<protected>
     */
    public function testFields()
    {
        $this->assertSame($this->fields, $this->error->fields());
    }
    
    protected function prepareAsString()
    {
        return "Invalid Fields: pixie, trixie";
    }
        
    protected function error()
    {
        return new \PHPixie\Validate\Errors\Error\Data\InvalidFields(
            $this->fields
        );
    }
}
