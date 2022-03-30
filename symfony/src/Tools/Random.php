<?php

namespace App\Tools;

class Random
{
    const BASE = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    static public function generate($size, $base = self::BASE) : string
    {
        do {
            $code  = '';
            $bytes = openssl_random_pseudo_bytes(1024);
            for ($i = 0; $i < 1024; $i++) {
                if (false !== strpos($base, $bytes[$i])) {
                    $code .= $bytes[$i];
                }
            }
        } while (strlen($code) < $size);

        return substr($code, 0, $size);
    }
}