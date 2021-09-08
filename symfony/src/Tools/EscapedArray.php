<?php

namespace App\Tools;

class EscapedArray extends \ArrayObject
{
    public function __construct($array = [], $flags = 0, $iteratorClass = "ArrayIterator")
    {
        foreach ($array as $index => $value) {
            $array[htmlentities($index)] = htmlentities($value);
        }

        parent::__construct($array, $flags, $iteratorClass);
    }
}