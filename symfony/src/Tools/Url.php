<?php

namespace App\Tools;

class Url
{
    public static function getAbsolute(string $relative)
    {
        return trim(getenv('WEBSITE_URL'), '/').$relative;
    }
}
