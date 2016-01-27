<?php

namespace PHPixie\Tests\Validate\Results;

/**
 * @coversDefaultClass \PHPixie\Validate\Results\Result
 */
abstract class ResultTest extends \PHPixie\Test\Testcase
{
    protected $results;
    protected $errors;
    
    protected $result;
    
    public function setUp()
    {
        $this->results = $this->quickMock('\PHPixie\Validate\Results');
        $this->errors  = $this->quickMock('\PHPixie\Validate\Errors');
        
        $this->result = $this->result();
    }
    
    /**
     * @covers ::__construct
     * @covers \PHPixie\Validate\Results\Result::__construct
     * @covers ::<protected>
     */
    public function testConstruct()
    {
        
    }
    
    /**
     * @covers ::addError
     * @covers ::errors
     * @covers ::<protected>
     */
    public function testErrors()
    {
        $errors = array();
        
        for($i=0; $i<5; $i++) {
            $error = $this->getError();
            $this->assertSame($this->result, $this->result->addError($error));
            $errors[]= $error;
        }
        
        $this->assertSame($errors, $this->result->errors());
    }
    
    /**
     * @covers ::field
     * @covers ::fields
     * @covers ::<protected>
     */
    public function testFields()
    {
        $this->result = $this->resultMock(array('buildFieldResult'));
        
        $results = array();
        
        foreach(array('pixie', 'trixie') as $field) {
            $result = $this->addField($field);
            
            for($i=0; $i<2; $i++) {
                $this->assertSame($result, $this->result->field($field));
            }
            
            $results[$field] = $result;
        }
        
        $this->assertSame($results, $this->result->fields());
    }
    
    /**
     * @covers ::invalidFields
     * @covers ::<protected>
     */
    public function testInvalidFieldResuls()
    {
        $expect = array();
        $this->result = $this->resultMock(array('buildFieldResult'));
        
        foreach(array('pixie', 'trixie', 'fairy') as $field) {
            $result = $this->addField($field);
            
            $isValid = $field === 'pixie';
            $this->method($result, 'isValid', $isValid, array(), 0);
            
            if(!$isValid) {
                $expect[$field] = $result;
            }
        }
        
        $this->assertSame($expect, $this->result->invalidFields());
    }
    
    /**
     * @covers ::isValid
     * @covers ::<protected>
     */
    public function testIsValid()
    {
        $this->isValidTest(true);
        $this->isValidTest(false, true);
        $this->isValidTest(false, false);
    }
    
    protected function isValidTest($withErrors = false, $withInvalidField = false)
    {
        $this->result = $this->resultMock(array('buildFieldResult'));
        
        if($withErrors) {
            $error = $this->abstractMock('\PHPixie\Validate\Errors\Error');
            $this->result->addError($error);
        }else{
            for($i=0; $i<2; $i++) {
                $result = $this->addField('pixie'.$i);
                $fieldValid = $i==0 || !$withInvalidField;
                $this->method($result, 'isValid', $fieldValid, array(), 0);
            }
        }
        
        $isValid = !$withErrors && !$withInvalidField;
        $this->assertSame($isValid, $this->result->isValid());
    }
    
    /**
     * @covers ::addEmptyValueError
     * @covers ::addFilterError
     * @covers ::addMessageError
     * @covers ::addCustomError
     * @covers ::addArrayTypeError
     * @covers ::addScalarTypeError
     * @covers ::addDocumentTypeError
     * @covers ::addArrayCountError
     * @covers ::<protected>
     */
    public function testAddErrors()
    {
        $sets = array(
            array('emptyValue', 'EmptyValue'),
            array('filter', 'Filter', array('email'), array('email', array())),
            array('filter', 'Filter', array('between', array(3, 4))),
            array('message', 'Message', array('pixie')),
            array('custom', 'Custom', array('pixie', 'trixie')),
            array('custom', 'Custom', array('pixie'), array('pixie', null)),
            array('dataType', 'ValueType\DocumentType'),
            array('scalarType', 'ValueType\ScalarType'),
            array('itemCount', 'ArrayCount', array(5, 3, 4)),
            array('itemCount', 'ArrayCount', array(5, 3), array(5, 3, null))
        );
        
        foreach($sets as $set) {
            $method = 'add'.ucfirst($set[0]).'Error';
            $error  = $this->getError($set[1]);
            
            $args     = isset($set[2]) ? $set[2] : array();
            $withArgs = isset($set[3]) ? $set[3] : $args;
            
            $this->method($this->errors, $set[0], $error, $withArgs, 0);
            $return = call_user_func_array(array($this->result, $method), $args);
            
            $this->assertSame($this->result, $return);
            
            $errors = $this->result->errors();
            $this->assertSame($error, end($errors));
        }
    }
    
    protected function addField($field)
    {
        $result = $this->getFieldResult();
        $this->method($this->result, 'buildFieldResult', $result, array($field), 0);
        $this->result->field($field);
        return $result;
    }
    
    protected function getError($class = null)
    {
        $fullClass = '\PHPixie\Validate\Errors\Error';
        if($class !== null) {
            $fullClass.= '\\'.$class;
        }
        
        return $this->quickMock($fullClass);
    }
    
    protected function getFieldResult()
    {
        return $this->quickMock('\PHPixie\Validate\Results\Result\Field');
    }
    
    abstract protected function result();
    abstract protected function resultMock($methods);
}
