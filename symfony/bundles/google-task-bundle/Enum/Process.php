<?php

namespace Bundles\GoogleTaskBundle\Enum;

use MyCLabs\Enum\Enum;

/**
 * @method static $this APP_ENGINE
 * @method static $this HTTP
 */
class Process extends Enum
{
    private const APP_ENGINE = 'app engine';
    private const HTTP       = 'http';

    public function isAppEngine() : bool
    {
        return self::APP_ENGINE === $this->value;
    }

    public function isHttp() : bool
    {
        return self::HTTP === $this->value;
    }
}
