<?php

namespace App\Provider\SMS;

interface SMSProvider
{
    /**
     * @param string $phoneNumber
     * @param string $message
     * @param array  $context
     *
     * @return SMSSent
     */
    public function send(string $phoneNumber, string $message, array $context = []): SMSSent;
}