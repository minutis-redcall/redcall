<?php

namespace App\Provider\Email;

interface EmailProvider
{
    public function send(string $to, string $subject, string $textBody, string $htmlBody);
}