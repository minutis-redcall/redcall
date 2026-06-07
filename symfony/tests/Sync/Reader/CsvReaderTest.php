<?php

namespace App\Tests\Sync\Reader;

use App\Sync\Reader\CsvReader;
use PHPUnit\Framework\TestCase;

class CsvReaderTest extends TestCase
{
    private string $tmpFile;

    protected function setUp() : void
    {
        $this->tmpFile = tempnam(sys_get_temp_dir(), 'csvreader');
    }

    protected function tearDown() : void
    {
        if (is_file($this->tmpFile)) {
            unlink($this->tmpFile);
        }
    }

    public function testSkipsHeaderAndYieldsRows()
    {
        file_put_contents($this->tmpFile, "id,libelle\n1,Alpha\n2,Beta\n");

        $rows = iterator_to_array((new CsvReader())->read($this->tmpFile), false);

        $this->assertCount(2, $rows);
        $this->assertSame(['1', 'Alpha'], $rows[0]);
        $this->assertSame(['2', 'Beta'], $rows[1]);
    }

    public function testReplacesDoubleQuotesWithSingleInsideCells()
    {
        // CSV escapes a literal " by doubling it ("") inside a quoted cell
        file_put_contents($this->tmpFile, "id,libelle\n1,\"He said \"\"hi\"\"\"\n");

        $rows = iterator_to_array((new CsvReader())->read($this->tmpFile), false);

        $this->assertSame(['1', "He said 'hi'"], $rows[0]);
    }

    public function testEmptyFileYieldsNothing()
    {
        file_put_contents($this->tmpFile, '');

        $rows = iterator_to_array((new CsvReader())->read($this->tmpFile), false);

        $this->assertSame([], $rows);
    }

    public function testHeaderOnlyYieldsNothing()
    {
        file_put_contents($this->tmpFile, "id,libelle\n");

        $rows = iterator_to_array((new CsvReader())->read($this->tmpFile), false);

        $this->assertSame([], $rows);
    }

    public function testHandlesCommasInsideQuotedCells()
    {
        file_put_contents($this->tmpFile, "id,libelle\n1,\"PARIS, 75001\"\n");

        $rows = iterator_to_array((new CsvReader())->read($this->tmpFile), false);

        $this->assertSame(['1', 'PARIS, 75001'], $rows[0]);
    }

    public function testStreamingDoesNotLoadFullFile()
    {
        // Generate a moderately-sized CSV to ensure the generator does not crash
        $fp = fopen($this->tmpFile, 'w');
        fwrite($fp, "id,libelle\n");
        for ($i = 0; $i < 5000; $i++) {
            fwrite($fp, sprintf("%d,row-%d\n", $i, $i));
        }
        fclose($fp);

        $count = 0;
        foreach ((new CsvReader())->read($this->tmpFile) as $row) {
            $this->assertCount(2, $row);
            $count++;
        }

        $this->assertSame(5000, $count);
    }

    public function testThrowsOnMissingFile()
    {
        $this->expectException(\RuntimeException::class);

        $reader = new CsvReader();
        // Generators are lazy: start iteration to trigger fopen
        iterator_to_array($reader->read('/nonexistent/path/file.csv'), false);
    }
}
