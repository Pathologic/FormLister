<?php

namespace PHPixie\Tests\Validate\Errors\Error\Data;

/**
 * @coversDefaultClass \PHPixie\Validate\Errors\Error\Data\ItemCount
 */
class ItemCountTest extends \PHPixie\Tests\Validate\Errors\ErrorTest
{
    protected $type = 'itemCount';
    
    protected $minCount = 3;
    protected $maxCount = 4;
    protected $count    = 5;
        
    /**
     * @covers ::__construct
     * @covers ::<protected>
     */
    public function testConstruct()
    {
        
    }
    
    /**
     * @covers ::__construct
     * @covers ::<protected>
     */
    public function testConstructNoMinMax()
    {
        $self = $this;
        $this->assertException(function() use($self) {
            new \PHPixie\Validate\Errors\Error\Data\ItemCount(5, null);
        }, '\PHPixie\Validate\Exception');
    }
    
    /**
     * @covers ::count
     * @covers ::minCount
     * @covers ::maxCount
     * @covers ::<protected>
     */
    public function testAttributes()
    {
        foreach(array('count', 'minCount', 'maxCount') as $name) {
            $this->assertSame($this->$name, $this->error->$name());
        }
    }
    
    /**
     * @covers ::asString
     * @covers ::<protected>
     */
    public function testStringMessages()
    {
        $error = $this->buildError(2, 3, null);
        $message = "Item count 2 is not greater or equal to 3";
        $this->assertSame($message, $error->asString());
        
        $error = $this->buildError(2, null, 1);
        $message = "Item count 2 is not less or equal to 1";
        $this->assertSame($message, $error->asString());
    }
    
    protected function prepareAsString()
    {
        return "Item count 5 is not between 3 and 4";
    }
        
    protected function error()
    {
        return $this->buildError(
            $this->count,
            $this->minCount,
            $this->maxCount
        );
    }
    
    protected function buildError($count, $minCount, $maxCount)
    {
        return new \PHPixie\Validate\Errors\Error\Data\ItemCount(
            $count,
            $minCount,
            $maxCount
        );
    }
}
