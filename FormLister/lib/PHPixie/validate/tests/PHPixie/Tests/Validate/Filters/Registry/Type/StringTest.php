<?php

namespace PHPixie\Tests\Validate\Filters\Registry\Type;

/**
 * @coversDefaultClass \PHPixie\Validate\Filters\Registry\Type\String
 */ 
class StringTest extends \PHPixie\Tests\Validate\Filters\Registry\ImplementationTest
{
    protected $sets = array(
        'length' => array(
            array('trixie', array(6), true),
            array('trixie', array(1), false)
        ),
        'minLength' => array(
            array('trixie', array(5), true),
            array('trixie', array(6), true),
            array('trixie', array(7), false)
        ),
        'maxLength' => array(
            array('trixie', array(7), true),
            array('trixie', array(6), true),
            array('trixie', array(4), false)
        ),
        'lengthBetween' => array(
            array('trixie', array(5, 7), true),
            array('trixie', array(6, 6), true),
            array('trixie', array(4, 5), false),
            array('trixie', array(7, 8), false)
        )
    );
    
    protected function registry()
    {
        return new \PHPixie\Validate\Filters\Registry\Type\String();
    }
}