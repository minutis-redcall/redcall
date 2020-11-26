<?php

namespace App\Provider\SMS;

interface SMSProvider
{
    public function send(string $from, string $to, string $message, array $context = []) : ?string;
}
