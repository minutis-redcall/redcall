<?php

namespace App\Contract;

interface PhoneInterface
{
    public function getE164() : ?string;
}
