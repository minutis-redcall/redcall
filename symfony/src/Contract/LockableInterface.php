<?php

namespace App\Contract;

interface LockableInterface
{
    public function isLocked() : ?bool;
}