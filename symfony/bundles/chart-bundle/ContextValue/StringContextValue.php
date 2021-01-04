<?php

namespace Bundles\ChartBundle\ContextValue;

use Doctrine\DBAL\ParameterType;

class StringContextValue implements ContextValueInterface
{
    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    static public function createFromJson(string $jsonValue) : ContextValueInterface
    {
        return new self(json_decode($jsonValue, true));
    }

    public function jsonSerialize()
    {
        return json_encode($this->value);
    }

    public function toHumanReadable() : string
    {
        return $this->value;
    }

    public function getSQLType()
    {
        return ParameterType::INTEGER;
    }

    public function getSQLValue()
    {
        return $this->value;
    }
}
