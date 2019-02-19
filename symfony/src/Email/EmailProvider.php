<?php

namespace App\Email;

interface EmailProvider
{
    public function send(string $to, string $subject, string $body);
}