<?php
namespace FormLister;

class Validator
{
    public function required($value) {
        return !in_array($value, array(null, ''), true);
    }

    public function min($value, $min)
    {
        return $value >= $min;
    }

    public function max($value, $max)
    {
        return $value <= $max;
    }

    public function greater($value, $min)
    {
        return $value > $min;
    }

    public function less($value, $max)
    {
        return $value < $max;
    }

    public function between($value, $min, $max)
    {
        return ($value >= $min && $value <= $max);
    }

    public function equals($value, $allowed)
    {
        return $value === $allowed;
    }

    public function in($value, $allowed)
    {
        return in_array($value, $allowed, true);
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
    public function length($value, $length)
    {
        return $this->getLength($value) === $length;
    }

    public function minLength($value, $minLength)
    {
        return $this->getLength($value) >= $minLength;
    }

    public function maxLength($value, $maxLength)
    {
        return $this->getLength($value) <= $maxLength;
    }

    public function lengthBetween($value, $minLength, $maxLength)
    {
        $length = $this->getLength($value);
        return ($length >= $minLength && $length <= $maxLength);
    }

    public function minSize($value, $minSize) {
        return count($value) >= $minSize;
    }

    public function maxSize($value, $maxSize) {
        return count($value) <= $maxSize;
    }

    public function sizeBetween($value, $minSize, $maxSize) {
        return (count($value) >= $minSize && count($value) <= $maxSize);
    }

    protected function getLength($string)
    {
        return strlen(utf8_decode($string));
    }
}