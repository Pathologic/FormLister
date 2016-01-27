<?php

namespace PHPixie\Validate\Errors\Error\Data;

class ItemCount extends \PHPixie\Validate\Errors\Error
{
    protected $count;
    protected $minCount;
    protected $maxCount;
    
    public function __construct($count, $minCount, $maxCount = null)
    {
        if($minCount === null && $maxCount === null) {
            throw new \PHPixie\Validate\Exception("Neither minimum nor maximum count specified.");
        }
        
        $this->count = $count;
        $this->minCount   = $minCount;
        $this->maxCount   = $maxCount;
    }
    
    public function minCount()
    {
        return $this->minCount;
    }
    
    public function maxCount()
    {
        return $this->maxCount;
    }
    
    public function count()
    {
        return $this->count;
    }
    
    public function type()
    {
        return 'itemCount';
    }
    
    public function asString()
    {
        $prefix = "Item count {$this->count} is not ";
        if($this->minCount !== null && $this->maxCount !== null) {
            return $prefix."between {$this->minCount} and $this->maxCount";
        }
        
        if($this->maxCount === null) {
            return $prefix."greater or equal to {$this->minCount}";
        }
        
        return $prefix."less or equal to {$this->maxCount}";
    }
    
}
