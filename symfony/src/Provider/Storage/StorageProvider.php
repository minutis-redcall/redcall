<?php

namespace App\Provider\Storage;

interface StorageProvider
{
    public function store(string $filename, string $content, ?string $contentType = null) : string;

    public function getRetentionDays() : int;
}
