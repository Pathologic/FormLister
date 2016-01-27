<?php

namespace PHPixie\Tests\Validate;

/**
 * @coversDefaultClass \PHPixie\Validate\Errors
 */
class ErrorsTest extends \PHPixie\Test\Testcase
{
    protected $errors;

    public function setUp()
    {
        $this->errors = new \PHPixie\Validate\Errors();
    }

    /**
     * @covers ::emptyValue
     * @covers ::<protected>
     */
    public function testEmptyValue()
    {
        $this->assertInstance(
            $this->errors->emptyValue(),
            '\PHPixie\Validate\Errors\Error\EmptyValue'
        );
    }

    /**
     * @covers ::filter
     * @covers ::<protected>
     */
    public function testFilter()
    {
        $class = '\PHPixie\Validate\Errors\Error\Filter';
        
        $parameters = array(5);
        $error = $this->errors->filter('pixie', $parameters);
        $this->assertInstance($error, $class, array(
            'filter'     => 'pixie',
            'parameters' => $parameters
        ));
        
        $error = $this->errors->filter('pixie');
        $this->assertInstance($error, $class, array(
            'filter'     => 'pixie',
            'parameters' => array()
        ));
    }

    /**
     * @covers ::message
     * @covers ::<protected>
     */
    public function testMessage()
    {
        $error = $this->errors->message('pixie');
        $this->assertInstance($error, '\PHPixie\Validate\Errors\Error\Message', array(
            'message' => 'pixie'
        ));
    }

    /**
     * @covers ::custom
     * @covers ::<protected>
     */
    public function testCustom()
    {
        $class = '\PHPixie\Validate\Errors\Error\Custom';

        $error = $this->errors->custom('pixie', 'trixie');
        $this->assertInstance($error, $class, array(
            'customType'  => 'pixie',
            'stringValue' => 'trixie'
        ));

        $error = $this->errors->custom('pixie');
        $this->assertInstance($error, $class, array(
            'customType'  => 'pixie',
            'stringValue' => null
        ));
    }
    
    /**
     * @covers ::dataType
     * @covers ::<protected>
     */
    public function testDataType()
    {
        $this->assertInstance(
            $this->errors->dataType(),
            '\PHPixie\Validate\Errors\Error\ValueType\Data'
        );
    }
    
    /**
     * @covers ::scalarType
     * @covers ::<protected>
     */
    public function testScalarType()
    {
        $this->assertInstance(
            $this->errors->scalarType(),
            '\PHPixie\Validate\Errors\Error\ValueType\Scalar'
        );
    }
    
    /**
     * @covers ::itemCount
     * @covers ::<protected>
     */
    public function testItemCount()
    {
        $class = '\PHPixie\Validate\Errors\Error\Data\ItemCount';
        
        $error = $this->errors->itemCount(4, 3, 5);
        $this->assertInstance($error, $class, array(
            'count'    => 4,
            'minCount' => 3,
            'maxCount' => 5
        ));
        
        $error = $this->errors->itemCount(4, 3);
        $this->assertInstance($error, $class, array(
            'count'    => 4,
            'minCount' => 3,
            'maxCount' => null
        ));
    }
    
    /**
     * @covers ::invalidFields
     * @covers ::<protected>
     */
    public function testInvalidFields()
    {
        $fields = array('pixie');
        $error = $this->errors->invalidFields($fields);
        
        $class = '\PHPixie\Validate\Errors\Error\Data\InvalidFields';
        $this->assertInstance($error, $class, array(
            'fields' => $fields
        ));
    }
}
