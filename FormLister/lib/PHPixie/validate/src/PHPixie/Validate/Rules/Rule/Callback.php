<?php

namespace PHPixie\Validate\Rules\Rule;

class Callback implements \PHPixie\Validate\Rules\Rule
{
    protected $callback;
    
    public function __construct($callback)
    {
        $this->callback = $callback;
    }
    
    public function validate($value, $result)
    {
        $callback = $this->callback;
        $callback($result, $value);
    }
}
