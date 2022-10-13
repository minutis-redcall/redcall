<?php

namespace App\Component\HttpFoundation;

use Symfony\Component\HttpFoundation\Response;

class DownloadResponse extends Response
{
    public function __construct(string $filename, string $content, int $status = Response::HTTP_OK, array $headers = [])
    {
        parent::__construct($content, $status, array_merge($headers, [
            'Content-Description'       => 'File Transfer',
            'Content-Transfer-Encoding' => 'binary',
            'Cache-Control'             => 'public, must-revalidate, max-age=0',
            'Pragma'                    => 'public',
            'Expires'                   => 'Tue, 10 Jul 1984 04:37:00 GMT',
            'Last-Modified'             => gmdate('D, d M Y H:i:s').' GMT',
            'Content-Type'              => 'octet/stream',
            'Content-Disposition'       => 'attachment; filename="'.$filename.'"',
        ]));
    }
}