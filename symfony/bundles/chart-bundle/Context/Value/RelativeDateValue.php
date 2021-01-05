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
        if (0 === $this->amount) {
            return $translator->trans('chart.context.relative_date.now');
        } else {
            return $translator->trans('chart.context.relative_date.amount', [
                '%%amount%' => $this->amount,
                '%unit%'    => $translator->trans(sprintf('chart.context.relative_date.unit_%s', $this->unit)),
            ]);
        }
    }

    public function getSQLType()
    {
        return ParameterType::STRING;
    }

    public function getSQLValue()
    {
        $period = 'P';
        foreach (array_reverse(self::UNITS) as $unit) {
            if (self::UNIT_HOUR === $unit) {
                $period .= 'T';
            }
            if ($this->unit === $unit) {
                $period .= $this->amount;
            }
            $period .= strtoupper(substr($unit, 0, 1));
        }

        return (new \DateTime())->sub(new \DateInterval($period))->format('Y-m-d H:i:s');
    }

    public function jsonSerialize()
    {
        return [
            'amount' => $this->amount,
            'unit'   => $this->unit,
        ];
    }
}