<?php

namespace Bundles\ChartBundle\ContextValue;

interface ContextValueInterface extends \JsonSerializable
{
    static public function createFromJson(string $jsonValue) : ContextValueInterface;

    public function toHumanReadable() : string;

    public function getSQLType();

    public function getSQLValue();
}
