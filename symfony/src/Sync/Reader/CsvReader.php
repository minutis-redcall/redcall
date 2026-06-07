<?php

namespace App\Sync\Reader;

class CsvReader
{
    /**
     * Count the data rows in a CSV (header excluded). Single linear pass,
     * counts newline bytes — fast even on large files. Used to pre-size
     * progress bars before the ingestion loop.
     */
    public function countRows(string $path) : int
    {
        if (!is_readable($path)) {
            throw new \RuntimeException(sprintf('Cannot open CSV file "%s".', $path));
        }

        $fp = fopen($path, 'r');
        if (false === $fp) {
            throw new \RuntimeException(sprintf('Cannot open CSV file "%s".', $path));
        }

        try {
            $lines = 0;
            while (!feof($fp)) {
                $chunk = fread($fp, 65536);
                if (false === $chunk) {
                    break;
                }
                $lines += substr_count($chunk, "\n");
            }

            // Drop the header line. Also guard against files that don't end
            // with a trailing newline (one data row remains uncounted).
            return max(0, $lines - 1);
        } finally {
            fclose($fp);
        }
    }

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
