<?php

namespace App\Provider\Call;

interface CallProvider
{
    public function send(string $from, string $to, array $context = []) : ?string;
}
