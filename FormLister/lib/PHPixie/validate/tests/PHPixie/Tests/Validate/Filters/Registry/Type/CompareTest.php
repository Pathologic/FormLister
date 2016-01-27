<?php

namespace PHPixie\Tests\Validate\Filters\Registry\Type;

/**
 * @coversDefaultClass \PHPixie\Validate\Filters\Registry\Type\Compare
 */ 
class CompareTest extends \PHPixie\Tests\Validate\Filters\Registry\ImplementationTest
{
    protected $sets = array(
        'min' => array(
            array(5, array(4), true),
            array(5, array(5), true),
            array(5, array(6), false)
        ),
        'max' => array(
            array(5, array(6), true),
            array(5, array(5), true),
            array(5, array(4), false)
        ),
        'greater' => array(
            array(5, array(4), true),
            array(5, array(5), false),
            array(5, array(6), false)
        ),
        'less' => array(
            array(5, array(6), true),
            array(5, array(5), false),
            array(5, array(4), false)
        ),
        'between' => array(
            array(5, array(4, 6), true),
            array(5, array(5, 5), true),
            array(5, array(3, 4), false),
            array(5, array(6, 7), false)
        ),
        'equals' => array(
            array(5, array(5), true),
            array(5, array(4), false)
        ),
        'in' => array(
            array(5, array(array(5, 6)), true),
            array(5, array(array(4)), false)
        )
    );
    
    protected function registry()
    {
        return new \PHPixie\Validate\Filters\Registry\Type\Compare();
    }
}