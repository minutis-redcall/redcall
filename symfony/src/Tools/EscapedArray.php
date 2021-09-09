<?php

namespace App\Tools;

class EscapedArray extends \ArrayObject
{
    public function __construct($array = [], $flags = 0, $iteratorClass = "ArrayIterator")
    {
        $copy = [];

        foreach ($array as $index => $value) {
            $copy[$this->escape($index)] = $this->escape($value);
        }

        parent::__construct($copy, $flags, $iteratorClass);
    }

    private function escape(string $value) : string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5);
    }
}