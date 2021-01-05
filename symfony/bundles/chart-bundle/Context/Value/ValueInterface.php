<?php

namespace Bundles\ChartBundle\Context\Value;

use Symfony\Contracts\Translation\TranslatorInterface;

interface ValueInterface extends \JsonSerializable
{
    static public function createFromJson(string $jsonValue) : ValueInterface;

    public function toHumanReadable(TranslatorInterface $translator) : string;

    public function getSQLType();

    public function getSQLValue();
}
