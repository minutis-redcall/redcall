<?php

namespace Bundles\GoogleTaskBundle\Security;

class Signer
{
    static public function sign(string $name, array $context) : string
    {
        $json = json_encode([
            'name'    => $name,
            'context' => $context,
        ]);

        return base64_encode(hash_hmac('sha1', $json, getenv('APP_SECRET'), true));
    }

    static public function verify(array $payload) : bool
    {
        foreach (['name', 'context', 'signature'] as $key) {
            if (!array_key_exists($key, $payload)) {
                return false;
            }
        }

        return $payload['signature'] === self::sign($payload['name'], $payload['signature']);
    }
}