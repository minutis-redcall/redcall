<?php

namespace App\Provider\Storage;

interface StorageProvider
{
    public function store(string $filename, string $content) : string;

    public function getRetentionDays() : int;
}
