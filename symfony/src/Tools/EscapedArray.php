<?php

namespace App\Tools;

class EscapedArray extends \ArrayObject
{
    public function __construct($array = [], $flags = 0, $iteratorClass = "ArrayIterator")
    {
        $copy = [];

        foreach ($array as $index => $value) {
            $copy[htmlentities($index)] = htmlentities($value);
        }

        parent::__construct($copy, $flags, $iteratorClass);
    }
}