<?php

namespace App\Tools;

class Hash
{
    static public function hash(string $text) : string
    {
        return hash_hmac('SHA256', $text, getenv('SECRET'));
    }
}