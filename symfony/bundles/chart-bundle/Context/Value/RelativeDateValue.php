<?php

namespace Bundles\ChartBundle\Context\Value;

use Doctrine\DBAL\ParameterType;
use Symfony\Contracts\Translation\TranslatorInterface;

class RelativeDateValue implements ValueInterface
{
    public const UNITS = [
        self::UNIT_SECOND,
        self::UNIT_MINUTE,
        self::UNIT_HOUR,
        self::UNIT_DAY,
        self::UNIT_WEEK,
        self::UNIT_MONTH,
        self::UNIT_YEAR,
    ];

    private const UNIT_SECOND = 'second';
    private const UNIT_MINUTE = 'minute';
    private const UNIT_HOUR   = 'hour';
    private const UNIT_DAY    = 'day';
    private const UNIT_WEEK   = 'week';
    private const UNIT_MONTH  = 'month';
    private const UNIT_YEAR   = 'year';

    /**
     * @var int
     */
    private $amount;

    /**
     * @var string
     */
    private $unit;

    public function __construct(int $amount, string $unit)
    {
        $this->amount = $amount;
        $this->unit   = $unit;
    }

    static public function createFromJson(string $jsonValue) : ValueInterface
    {
        $data = json_decode($jsonValue);

        return new self($data['amount'] ?? 0, $data['unit'] ?? self::UNIT_SECOND);
    }

    public function toHumanReadable(TranslatorInterface $translator) : string
    {
        // TODO: Implement toHumanReadable() method.
    }

    public function getSQLType()
    {
        return ParameterType::STRING;
    }

    public function getSQLValue()
    {
        // TODO: Implement getSQLValue() method.
    }

    public function jsonSerialize()
    {
        return [
            'amount' => $this->amount,
            'unit'   => $this->unit,
        ];
    }
}