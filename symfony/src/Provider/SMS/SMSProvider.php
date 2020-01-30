<?php

namespace App\Provider\SMS;

use Exception;

interface SMSProvider
{
    /**
     * @param string $message
     * @param string $phoneNumber
     *
     * @return string
     * @throws Exception
     */
    public function send(string $message, string $phoneNumber): SMSSent;

    /**
     * @return string
     */
    public function getProviderCode(): string;
}