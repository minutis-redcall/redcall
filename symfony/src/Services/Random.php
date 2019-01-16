<?php

namespace App\Services;

class Random
{
    const BASE = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    static public function generate($size)
    {
        list($usec, $sec) = explode(' ', microtime());
        $seed = (float)$sec + ((float)$usec * 100000);
        mt_srand($seed);

        $base = self::BASE;
        $code = '';

        for ($i = 0; $i < $size; $i++) {
            $code .= $base[mt_rand() % mb_strlen($base)];
        }

        return $code;
    }
}