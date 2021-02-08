<?php

namespace Bundles\ChartBundle\Context\Value;

use Doctrine\DBAL\ParameterType;
use Symfony\Contracts\Translation\TranslatorInterface;

class AbsoluteDateValue implements ValueInterface
{
    private $value;

    public function __construct(\DateTime $value)
    {
        $this->value = $value;
    }

    static public function createFromJson(string $jsonValue) : ValueInterface
    {
        return new self(new \DateTime(json_decode($jsonValue, true)));
    }

    public function jsonSerialize()
    {
        return $this->value->format('Y-m-d 00:00:00');
    }

    public function toHumanReadable(TranslatorInterface $translator) : string
    {
        return $this->value->format('d/m/Y');
    }

    public function getSQLType()
    {
        return ParameterType::STRING;
    }

    public function getSQLValue()
    {
        return $this->value->format('Y-m-d 00:00:00');
    }
}
