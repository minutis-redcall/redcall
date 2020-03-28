<?php

namespace App\Provider\SMS;

interface SMSProvider
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