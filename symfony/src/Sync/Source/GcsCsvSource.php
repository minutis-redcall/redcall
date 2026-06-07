<?php

namespace App\Sync\Source;

use Google\Cloud\Storage\StorageClient;
use Google\Cloud\Storage\StorageObject;

class GcsCsvSource implements CsvSourceInterface
{
    private string $bucketName;
    private string $tmpDir;

    public function __construct(string $bucketName, ?string $tmpDir = null)
    {
        $this->bucketName = $bucketName;
        $this->tmpDir     = $tmpDir ?? sys_get_temp_dir();
    }

    public function download() : array
    {
        $bucket = (new StorageClient())->bucket($this->bucketName);

        $files = [];
        foreach ($bucket->objects() ?? [] as $item) {
            /** @var StorageObject $item */
            if (!preg_match('|^redcall_[a-z_]+\.csv$|u', $item->name())) {
                continue;
            }

            $localPath = sprintf('%s/sync_%s_%s', $this->tmpDir, bin2hex(random_bytes(4)), $item->name());
            $item->downloadToFile($localPath);

            $files[$item->name()] = $localPath;
        }

        return $files;
    }
}
