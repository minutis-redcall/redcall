<?php

namespace App\Sync\Source;

interface CsvSourceInterface
{
    /**
     * Make every CSV file available locally and return their absolute paths,
     * keyed by short filename (e.g. "redcall_benevoles.csv").
     *
     * @return array<string,string>
     */
    public function download() : array;
}
