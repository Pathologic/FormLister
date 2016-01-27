<?php

namespace PHPixie\Validate\Filters;

interface Registry
{
    public function callFilter($name, $value, $arguments);
    public function filters();
}