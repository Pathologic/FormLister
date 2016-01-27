<?php

namespace PHPixie\Validate\Filters\Registry;

abstract class Implementation implements \PHPixie\Validate\Filters\Registry
{
    public function callFilter($name, $value, $arguments)
    {
        if(!in_array($name, $this->filters(), true)) {
            throw new \PHPixie\Validate\Exception("Filter $name does not exist");
        }
        
        array_unshift($arguments, $value);
        return call_user_func_array(array($this, $name), $arguments);
    }
    
    abstract public function filters();
}