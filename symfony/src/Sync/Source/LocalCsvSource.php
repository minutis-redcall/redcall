<?php

namespace App\Sync\Source;

class LocalCsvSource implements CsvSourceInterface
{
    private string $rootDir;

    public function __construct(string $rootDir)
    {
        $this->rootDir = rtrim($rootDir, DIRECTORY_SEPARATOR);
    }

    public function download() : array
    {
        if (!is_dir($this->rootDir)) {
            throw new \RuntimeException(sprintf('CSV source directory "%s" does not exist.', $this->rootDir));
        }

        $files = [];
        foreach (scandir($this->rootDir) ?: [] as $entry) {
            if (!preg_match('|^redcall_[a-z_]+\.csv$|u', $entry)) {
                continue;
            }

            $files[$entry] = $this->rootDir.DIRECTORY_SEPARATOR.$entry;
        }

        return $files;
    }
}
