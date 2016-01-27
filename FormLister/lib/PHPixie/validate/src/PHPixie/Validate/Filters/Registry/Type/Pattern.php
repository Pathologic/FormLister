<?php

namespace PHPixie\Validate\Filters\Registry\Type;

class Pattern extends \PHPixie\Validate\Filters\Registry\Implementation
{
    public function filters()
    {
        return array(
            'alpha',
            'numeric',
            'alphaNumeric',
            'slug',
            'decimal',
            'phone',
            'matches',
            'url',
            'email'
        );
    }
    
    public function alpha($value)
    {
        return (bool) preg_match('/^\pL++$/uD', $value);
    }

    public function numeric($value)
    {
        return (bool) preg_match('#^[0-9]*$#',$value);
    }

    public function alphaNumeric($value)
    {
        return (bool) preg_match('/^[\pL\pN]++$/uD', $value);
    }

    public function slug($value)
    {
        return (bool) preg_match('/^[\pL\pN\-\_]++$/uD', $value);
    }

    public function decimal($value)
    {
        return (bool) preg_match('/^[0-9]+(?:\.[0-9]+)?$/D', $value);
    }


    public function phone($value)
    {
        return (bool) preg_match('#^[0-9\(\)\+ \-]*$#',$value);
    }

    public function matches($value,$regexp)
    {
        return (bool) preg_match($regexp,$value);
    }

    public function url($value)
    {
        return (bool) preg_match(
            '~^
                [-a-z0-9+.]++://
                (?!-)[-a-z0-9]{1,63}+(?<!-)
                (?:\.(?!-)[-a-z0-9]{1,63}+(?<!-)){0,126}+
                (?::\d{1,5}+)?
                (?:/.*)?
            $~iDx',
            $value);
    }

    public function email($value)
    {
        return (bool) preg_match(
            '/^
                [-_a-z0-9\'+*$^&%=~!?{}]++
                (?:\.[-_a-z0-9\'+*$^&%=~!?{}]+)*+
                @(?:(?![-.])[-a-z0-9.]+(?<![-.])\.
                [a-z]{2,6}|\d{1,3}(?:\.\d{1,3}){3})
                (?::\d++)?
            $/iDx',
            $value
        );
    }
}