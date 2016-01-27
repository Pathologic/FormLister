<?php

namespace PHPixie\Validate\Results\Result;

class Field extends \PHPixie\Validate\Results\Result
{
    protected $path;
    
    public function __construct($results, $errors, $rootResult, $path)
    {
        parent::__construct($results, $errors);
        
        $this->rootResult = $rootResult;
        $this->path       = $path;
    }
    
    public function path()
    {
        return $this->path;
    }
    
    public function getValue()
    {
        return $this->rootResult->getPathValue($this->path);
    }
    
    protected function buildFieldResult($path)
    {
        $path = $this->path.'.'.$path;
        return $this->results->field($this->rootResult, $path);
    }
}
