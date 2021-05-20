<?php

namespace App\Tools;

class Encryption
{
    const LAST_VERSION = 1;

    static public function encrypt(string $cleartext, string $salt = '') : string
    {
        $secret  = getenv('SECRET');
        $iv      = substr(openssl_digest($salt.$secret, 'SHA512'), 0, 16);
        $cypher  = openssl_encrypt($cleartext, 'AES-256-CBC', $secret, 0, $iv);
        $urlsafe = rtrim(strtr($cypher, '+/', '-_'), '=');

        return sprintf('%d-%s', self::LAST_VERSION, $urlsafe);
    }

    static public function decrypt(string $encrypted, string $salt = '') : string
    {
        $version = substr($encrypted, 0, strpos($encrypted, '-'));

        switch ($version) {
            case 1:
                $secret  = getenv('SECRET');
                $iv      = substr(openssl_digest($salt.$secret, 'SHA512'), 0, 16);
                $urlsafe = substr($encrypted, strpos($encrypted, '-') + 1);
                $cypher  = strtr($urlsafe, '-_', '+/');

                $cleartext = openssl_decrypt($cypher, 'AES-256-CBC', $secret, 0, $iv);
                if (!$cleartext) {
                    throw new \LogicException('Could not decrypt provided cypher');
                }

                return $cleartext;
        }

        throw new \LogicException('Invalid or missing encryption version');
    }
}