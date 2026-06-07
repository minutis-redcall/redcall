<?php

namespace App\Sync\Reader;

class CsvReader
{
    /**
     * Stream a CSV file row by row. The first row (header) is skipped.
     * Every cell has its double quotes replaced by single quotes to preserve
     * the sanitization behavior of the legacy import pipeline.
     *
     * @return iterable<int,array<int,string>>
     */
    public function read(string $path) : iterable
    {
        if (!is_readable($path)) {
            throw new \RuntimeException(sprintf('Cannot open CSV file "%s".', $path));
        }

        $fp = fopen($path, 'r');
        if (false === $fp) {
            throw new \RuntimeException(sprintf('Cannot open CSV file "%s".', $path));
        }

        try {
            $header = fgetcsv($fp, null, ',', '"', '\\');
            if (false === $header) {
                return;
            }

            while (false !== ($row = fgetcsv($fp, null, ',', '"', '\\'))) {
                foreach ($row as $index => $value) {
                    if (is_string($value)) {
                        $row[$index] = str_replace('"', "'", $value);
                    }
                }

                yield $row;
            }
        } finally {
            fclose($fp);
        }
    }
}
