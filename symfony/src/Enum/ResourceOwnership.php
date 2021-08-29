<?php

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * @method static $this KNOWN_RESOURCE
 * @method static $this RESOLVED_RESOURCE
 */
class ResourceOwnership extends Enum
{
    private const KNOWN_RESOURCE    = 'KNOWN_RESOURCE';
    private const RESOLVED_RESOURCE = 'RESOLVED_RESOURCE';
}