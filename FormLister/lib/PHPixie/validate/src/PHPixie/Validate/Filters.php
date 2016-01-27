<?php

namespace PHPixie\Validate;

class Filters
{
    protected $externalRegistries;
    protected $registries;
    protected $filterMap;
    
    public function __construct($externalRegistries = array())
    {
        $this->externalRegistries = $externalRegistries;
    }
    
    public function registries()
    {
        $this->requireRegistries();        
        return $this->registries;
    }
    
    public function filterMap()
    {
        $this->requireFilterMap();
        return $this->filterMap;
    }
    
    protected function requireRegistries()
    {
        if($this->registries !== null) {
            return;
        }
        
        $this->registries = array_merge(
            $this->buildRegistries(),
            $this->externalRegistries
        );
    }
    
    protected function requireFilterMap()
    {
        if($this->filterMap !== null) {
            return;
        }
        
        $this->requireRegistries();
        
        $filterMap = array();
        
        foreach($this->registries as $registryKey => $registry) {
            foreach($registry->filters() as $name) {
                $filterMap[$name] = $registryKey;
            }
        }
        
        $this->filterMap = $filterMap;
    }
    
    protected function buildRegistries()
    {
        return array(
            $this->buildCompareRegistry(),
            $this->buildPatternRegistry(),
            $this->buildStringRegistry()
        );
    }
    
    public function callFilter($name, $value, $arguments = array())
    {
        $this->requireFilterMap();
        
        if(!array_key_exists($name, $this->filterMap)) {
            throw new \PHPixie\Validate\Exception("Filter '$name' does not exist");
        }
        
        $registryKey = $this->filterMap[$name];
        $registry = $this->registries[$registryKey];
        return $registry->callFilter($name, $value, $arguments);
    }
    
    public function filter($name, $arguments = array())
    {
        return new Filters\Filter($this, $name, $arguments);
    }
    
    protected function buildCompareRegistry()
    {
        return new Filters\Registry\Type\Compare();
    }
    
    protected function buildPatternRegistry()
    {
        return new Filters\Registry\Type\Pattern();
    }
    
    protected function buildStringRegistry()
    {
        return new Filters\Registry\Type\String();
    }
}