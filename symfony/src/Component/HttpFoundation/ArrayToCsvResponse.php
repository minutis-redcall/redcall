<?php

namespace App\Component\HttpFoundation;

use League\Csv\Reader;
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\Response;

class ArrayToCsvResponse extends Response
{
    /**
     * ArrayToCsvResponse constructor.
     *
     * @param array  $array
     * @param string $filename
     * @param int    $status
     * @param array  $headers
     */
    public function __construct(array $array, string $filename, int $status = Response::HTTP_OK, array $headers = [])
    {
        parent::__construct($this->arrayToCsv($array), $status, array_merge($headers, [
            'Expires'                   => 'Tue, 10 Jul 1984 04:37:00 GMT',
            'Cache-Control'             => 'max-age=0, no-cache, must-revalidate, proxy-revalidate',
            'Last-Modified'             => gmdate("D, d M Y H:i:s"),
            'Content-Type'              => 'application/force-download',
            'Content-Type'              => 'application/octet-stream',
            'Content-Type'              => 'application/download',
            'Content-Disposition'       => sprintf('attachment;filename=%s', $filename),
            'Content-Transfer-Encoding' => 'binary',
        ]));
    }

    /**
     * @param array $array
     *
     * @return null|string
     */
    private function arrayToCsv(array &$array): ?string
    {
        if (count($array) == 0) {
            return null;
        }

        if (!ini_get("auto_detect_line_endings")) {
            ini_set("auto_detect_line_endings", '1');
        }

        $csv = Writer::createFromString('');
        $csv->setOutputBOM(Reader::BOM_UTF8);
        $csv->setDelimiter(';');
        $csv->insertOne(array_keys(reset($array)));
        $csv->insertAll($array);

        return $csv->getContent();
    }
}