<?php

namespace App\Enum;

use MyCLabs\Enum\Enum;

final class Group extends Enum
{
    public const GROUP_1  = 'FF5733'; // Red-Orange
    public const GROUP_2  = '33FF57'; // Lime Green
    public const GROUP_3  = '3357FF'; // Blue
    public const GROUP_4  = 'FF33A1'; // Pink
    public const GROUP_5  = 'A133FF'; // Purple
    public const GROUP_6  = '33FFF5'; // Cyan
    public const GROUP_7  = 'F5FF33'; // Yellow
    public const GROUP_8  = 'FF9633'; // Orange
    public const GROUP_9  = '3380FF'; // Azure
    public const GROUP_10 = '80FF33'; // Bright Green
    public const GROUP_11 = 'FF3333'; // Pure Red
    public const GROUP_12 = '3333FF'; // Pure Blue

    public static function getGroups() : array
    {
        return array_values(self::toArray());
    }
}
