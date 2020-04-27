<?php

namespace App\Services;

use Google\Cloud\Storage\StorageClient;

class Storage
{
    /**
     * @var StorageClient
     */
    private $client;

    public function store(string $filename, string $content): string
    {
        $bucket = $this->getClient()->bucket(
            getenv('GCP_STORAGE_BUCKET')
        );

        $stream = fopen('data://text/plain;base64,' . base64_encode($content),'r');

        $object = $bucket->upload(
            $stream,
            ['name' => $filename]
        );

        return $object->signedUrl(time() + 7 * 24 * 3600);
    }

    private function getClient(): StorageClient
    {
        if (!$this->client) {
            $this->client = new StorageClient();
        }

        return $this->client;
    }
}