<?php

namespace App\Provider\Call;

interface CallProvider
{
    /**
     * @param string $phoneNumber
     * @param string $message
     * @param array  $context
     *
     * @return string
     */
    public function send(string $phoneNumber, string $message, array $context = []): ?string;
}