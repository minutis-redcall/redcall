<?php

namespace App\Component\HttpFoundation;

use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Mpdf\Output\Destination;
use Symfony\Component\HttpFoundation\Response;

class MpdfResponse extends Response
{
    /**
     * MpdfResponse constructor.
     *
     * @param Mpdf   $mpdf
     * @param string $filename
     * @param int    $status
     * @param array  $headers
     *
     * @throws MpdfException
     */
    public function __construct(Mpdf $mpdf, string $filename, int $status = Response::HTTP_OK, array $headers = [])
    {
        $content = $mpdf->Output($filename, Destination::STRING_RETURN);

        parent::__construct($content, $status, array_merge($headers, [
            'Content-Description'       => 'File Transfer',
            'Content-Transfer-Encoding' => 'binary',
            'Cache-Control'             => 'public, must-revalidate, max-age=0',
            'Pragma'                    => 'public',
            'Expires'                   => 'Tue, 10 Jul 1984 04:37:00 GMT',
            'Last-Modified'             => gmdate('D, d M Y H:i:s').' GMT',
            'Content-Type'              => 'application/pdf',
            'Content-Disposition'       => 'attachment; filename="'.$filename.'"',
        ]));
    }
}