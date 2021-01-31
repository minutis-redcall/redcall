<?php

namespace App\Provider\Storage;

use Google\Cloud\Storage\StorageClient;

class GoogleCloudStorage implements StorageProvider
{
    const RETENTION = 365;

    /**
     * @var StorageClient
     */
    private $client;

    public function store(string $filename, string $content) : string
    {
        $bucket = $this->getClient()->bucket(
            getenv('GCP_STORAGE_BUCKET')
        );

        $stream = fopen('data://text/plain;base64,'.base64_encode($content), 'r');

        $object = $bucket->upload(
            $stream,
            ['name' => $filename]
        );

        return $object->signedUrl(time() + self::RETENTION * 24 * 3600);
    }

    public function getRetentionDays() : int
    {
        return self::RETENTION;
    }

    private function getClient() : StorageClient
    {
        if (!$this->client) {
            $this->client = new StorageClient();
        }

        return $this->client;
    }
}