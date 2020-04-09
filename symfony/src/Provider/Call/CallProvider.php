<?php

namespace App\Provider\Call;

interface CallProvider
{
    /**
     * @param string $phoneNumber Phone number to call
     * @param array  $context     App contextual data to attach a call to your business logic
     *
     * @return string|null
     */
    public function send(string $phoneNumber, array $context = []): ?string;
}
