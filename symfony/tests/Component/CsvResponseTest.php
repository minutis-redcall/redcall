<?php

namespace App\Tests\Component;

use App\Component\HttpFoundation\ArrayToCsvResponse;
use App\Component\HttpFoundation\DownloadResponse;
use App\Component\HttpFoundation\NoContentResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class CsvResponseTest extends TestCase
{
    // --- ArrayToCsvResponse ---

    public function testArrayToCsvResponseSetsCorrectHeaders()
    {
        $data = [
            ['name' => 'Alice', 'age' => '30'],
            ['name' => 'Bob', 'age' => '25'],
        ];

        $response = new ArrayToCsvResponse($data, 'export.csv');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('application/octet-stream', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('export.csv', $response->headers->get('Content-Disposition'));
        $this->assertSame('binary', $response->headers->get('Content-Transfer-Encoding'));
    }

    public function testArrayToCsvResponseContainsCsvData()
    {
        $data = [
            ['name' => 'Alice', 'age' => '30'],
            ['name' => 'Bob', 'age' => '25'],
        ];

        $response = new ArrayToCsvResponse($data, 'export.csv');
        $content  = $response->getContent();

        // Should contain header row and data rows separated by semicolons
        $this->assertStringContainsString('name', $content);
        $this->assertStringContainsString('age', $content);
        $this->assertStringContainsString('Alice', $content);
        $this->assertStringContainsString('Bob', $content);
    }

    public function testArrayToCsvResponseUsesSemicolonDelimiter()
    {
        $data = [
            ['col1' => 'val1', 'col2' => 'val2'],
        ];

        $response = new ArrayToCsvResponse($data, 'test.csv');
        $content  = $response->getContent();

        $this->assertStringContainsString(';', $content);
    }

    public function testArrayToCsvResponseWithEmptyArrayReturnsEmptyContent()
    {
        $response = new ArrayToCsvResponse([], 'empty.csv');

        $this->assertEmpty($response->getContent());
    }

    public function testArrayToCsvResponseWithCustomStatus()
    {
        $data = [
            ['name' => 'Alice'],
        ];

        $response = new ArrayToCsvResponse($data, 'export.csv', Response::HTTP_CREATED);

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
    }

    public function testArrayToCsvResponseIncludesUtf8Bom()
    {
        $data = [
            ['name' => 'Alice'],
        ];

        $response = new ArrayToCsvResponse($data, 'export.csv');
        $content  = $response->getContent();

        // UTF-8 BOM: EF BB BF
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
    }

    public function testArrayToCsvResponseSetsCacheHeaders()
    {
        $data = [
            ['name' => 'Alice'],
        ];

        $response = new ArrayToCsvResponse($data, 'export.csv');

        $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));
    }

    // --- NoContentResponse ---

    public function testNoContentResponseHas204Status()
    {
        $response = new NoContentResponse();

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testNoContentResponseHasEmptyBody()
    {
        $response = new NoContentResponse();

        $this->assertEmpty($response->getContent());
    }

    public function testNoContentResponseAcceptsCustomHeaders()
    {
        $response = new NoContentResponse(['X-Custom' => 'value']);

        $this->assertSame('value', $response->headers->get('X-Custom'));
    }

    // --- DownloadResponse ---

    public function testDownloadResponseSetsCorrectHeaders()
    {
        $response = new DownloadResponse('report.pdf', 'pdf-content');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('octet/stream', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('report.pdf', $response->headers->get('Content-Disposition'));
        $this->assertSame('binary', $response->headers->get('Content-Transfer-Encoding'));
    }

    public function testDownloadResponseContainsContent()
    {
        $response = new DownloadResponse('file.txt', 'file content here');

        $this->assertSame('file content here', $response->getContent());
    }

    public function testDownloadResponseWithCustomStatus()
    {
        $response = new DownloadResponse('file.txt', 'content', Response::HTTP_ACCEPTED);

        $this->assertSame(Response::HTTP_ACCEPTED, $response->getStatusCode());
    }

    public function testDownloadResponseSetsFileDescription()
    {
        $response = new DownloadResponse('file.txt', 'content');

        $this->assertSame('File Transfer', $response->headers->get('Content-Description'));
    }

    public function testDownloadResponseSetsCacheControlToPublic()
    {
        $response = new DownloadResponse('file.txt', 'content');

        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('public', $cacheControl);
    }

    public function testDownloadResponseSetsExpiresHeader()
    {
        $response = new DownloadResponse('file.txt', 'content');

        $this->assertNotNull($response->headers->get('Expires'));
    }

    public function testDownloadResponseFilenameInDisposition()
    {
        $response = new DownloadResponse('my-report.xlsx', 'binary-data');

        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('my-report.xlsx', $disposition);
        $this->assertStringContainsString('attachment', $disposition);
    }
}
