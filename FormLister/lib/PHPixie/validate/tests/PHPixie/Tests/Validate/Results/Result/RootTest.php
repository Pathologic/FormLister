<?php

namespace PHPixie\Tests\Validate\Results\Result\Root;

/**
 * @coversDefaultClass \PHPixie\Validate\Results\Result\Root
 */
class RootTest extends \PHPixie\Tests\Validate\Results\ResultTest
{
    public function setUp()
    {
        $this->value = array(
            'pixie' => array(
                'trixie' => (object) array(
                    'blum' => 5
                )
            ),
            'stella' => 7
        );
        
        parent::setUp();
    }
    
    /**
     * @covers ::getValue
     * @covers ::<protected>
     */
    public function testGetValue()
    {
        $this->assertSame($this->value, $this->result->getValue());
    }
    
    /**
     * @covers ::getPathValue
     * @covers ::<protected>
     */
    public function testPathValue()
    {
        $this->assertSame(5, $this->result->getPathValue('pixie.trixie.blum'));
        $this->assertNull($this->result->getPathValue('stella.blum'));
        $this->assertNull($this->result->getPathValue('pixie.blum'));
        $this->assertNull($this->result->getPathValue('pixie.trixie.stella'));
    }
    
    /**
     * @covers ::buildFieldResult
     * @covers ::<protected>
     */
    public function testBuildFieldResult()
    {
        $result = $this->getFieldResult();
        $this->method($this->results, 'field', $result, array($this->result, 'pixie'), 0);
        $this->assertSame($result, $this->result->field('pixie'));
    }
    
    protected function result()
    {
        return new \PHPixie\Validate\Results\Result\Root(
            $this->results,
            $this->errors,
            $this->value
        );
    }
    
    protected function resultMock($methods = null)
    {
        return $this->getMock(
            '\PHPixie\Validate\Results\Result\Root',
            $methods,
            array(
                $this->results,
                $this->errors,
                $this->value
            )
        );
    }
}
