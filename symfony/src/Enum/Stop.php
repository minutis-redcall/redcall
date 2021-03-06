<?php

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * @method static $this STOP
 * @method static $this ARRET
 */
final class Stop extends Enum
{
    private const STOP  = 'STOP';
    private const ARRET = 'ARRET';

    public static function isValid($value)
    {
        return parent::isValid(
            strtoupper($value)
        );
    }
}
