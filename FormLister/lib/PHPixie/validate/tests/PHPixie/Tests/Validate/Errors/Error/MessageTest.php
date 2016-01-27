<?php

namespace PHPixie\Tests\Validate\Errors\Error;

/**
 * @coversDefaultClass \PHPixie\Validate\Errors\Error\Message
 */ 
class MessageTest extends \PHPixie\Tests\Validate\Errors\ErrorTest
{
    protected $type    = 'message';
    protected $message = 'fairy';
    
    /**
     * @covers ::message
     * @covers ::<protected>
     */
    public function testMessage()
    {
        $this->assertSame($this->message, $this->error->message());
    }
    
    protected function prepareAsString()
    {
        return $this->message;
    }
        
    protected function error()
    {
        return new \PHPixie\Validate\Errors\Error\Message($this->message);
    }
}