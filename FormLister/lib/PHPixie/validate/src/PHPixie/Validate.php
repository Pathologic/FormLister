<?php

namespace PHPixie;

class Validate
{
    protected $builder;
    
    public function __construct()
    {
        $this->builder = $this->buildBuilder();
    }
    
    public function validator($callback = null)
    {
        $rule = $this->rules()->value();
        if($callback !== null) {
            $callback($rule);
        }
        return $this->buildValidator($rule);
    }
    
    public function buildValidator($rule)
    {
        return $this->builder->validator($rule);
    }
    
    public function rules()
    {
        return $this->builder->rules();
    }
    
    public function builder()
    {
        return $this->builder;
    }
    
    protected function buildBuilder()
    {
        return new Validate\Builder();
    }
}
