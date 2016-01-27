<?php

namespace PHPixie\Validate\Rules;

interface Rule
{
    public function validate($value, $result);
}
