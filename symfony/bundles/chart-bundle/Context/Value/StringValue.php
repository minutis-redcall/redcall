<?php

namespace Bundles\ChartBundle\Context\Value;

use Doctrine\DBAL\ParameterType;
use Symfony\Contracts\Translation\TranslatorInterface;

class StringValue implements ValueInterface
{
    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    static public function createFromJson(string $jsonValue) : ValueInterface
    {
        return new self(json_decode($jsonValue, true));
    }

    public function jsonSerialize()
    {
        return $this->value;
    }

    public function toHumanReadable(TranslatorInterface $translator) : string
    {
        return $this->value;
    }

    public function getSQLType()
    {
        return ParameterType::STRING;
    }

    public function getSQLValue()
    {
        return $this->value;
    }
}
