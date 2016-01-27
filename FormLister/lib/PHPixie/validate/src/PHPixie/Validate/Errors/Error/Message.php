<?php

namespace PHPixie\Validate\Errors\Error;

class Message extends \PHPixie\Validate\Errors\Error
{
    protected $message;
    
    public function __construct($message)
    {
        $this->message = $message;
    }
    
    public function type()
    {
        return 'message';
    }
    
    public function message()
    {
        return $this->message;
    }
    
    public function asString()
    {
        return $this->message;
    }
}