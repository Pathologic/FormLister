<?php

namespace PHPixie\Tests\Validate\Filters\Registry\Type;

/**
 * @coversDefaultClass \PHPixie\Validate\Filters\Registry\Type\Pattern
 */ 
class PatternTest extends \PHPixie\Tests\Validate\Filters\Registry\ImplementationTest
{
    protected $sets = array(
        'alpha' => array(
            array('pixie', array(), true),
            array('pixie4', array(), false)
        ),
        'numeric' => array(
            array('44', array(), true),
            array('pixie4', array(), false)
        ),
        'alphaNumeric' => array(
            array('pixie4', array(), true),
            array('pixie-4', array(), false)
        ),
        'slug' => array(
            array('pixie-4', array(), true),
            array('pixie_4', array(), true),
            array('pixie 4', array(), false)
        ),
        'decimal' => array(
            array(10, array(), true),
            array(10.5, array(), true),
            array('p', array(), false),
        ),
        'phone' => array(
            array('+10 123 123', array(), true),
            array('test', array(), false),
        ),
        'matches' => array(
            array('test', array('#^[a-z]*$#'), true),
            array('test4', array('#^[a-z]*$#'), false)
        ),
        'url' => array(
            array('http://phpixie.com', array(), true),
            array('test', array(), false),
        ),
        'email' => array(
            array('trixie@phpixie.com', array(), true),
            array('test', array(), false),
        )
    );
    
    protected function registry()
    {
        return new \PHPixie\Validate\Filters\Registry\Type\Pattern();
    }
}