<?php

namespace App\Tests\Sync\Source;

use App\Sync\Source\LocalCsvSource;
use PHPUnit\Framework\TestCase;

class LocalCsvSourceTest extends TestCase
{
    private string $dir;

    protected function setUp() : void
    {
        $this->dir = sys_get_temp_dir().'/localcsv_'.bin2hex(random_bytes(4));
        mkdir($this->dir);
    }

    protected function tearDown() : void
    {
        foreach (glob($this->dir.'/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->dir);
    }

    public function testReturnsRedcallCsvFiles()
    {
        file_put_contents($this->dir.'/redcall_benevoles.csv', '');
        file_put_contents($this->dir.'/redcall_ref_formations.csv', '');
        file_put_contents($this->dir.'/other_file.txt', '');
        file_put_contents($this->dir.'/redcall_invalid.json', '');

        $files = (new LocalCsvSource($this->dir))->download();

        $this->assertArrayHasKey('redcall_benevoles.csv', $files);
        $this->assertArrayHasKey('redcall_ref_formations.csv', $files);
        $this->assertArrayNotHasKey('other_file.txt', $files);
        $this->assertArrayNotHasKey('redcall_invalid.json', $files);
        $this->assertStringEndsWith('/redcall_benevoles.csv', $files['redcall_benevoles.csv']);
    }

    public function testThrowsWhenDirectoryMissing()
    {
        $this->expectException(\RuntimeException::class);

        (new LocalCsvSource('/nonexistent/path/'.bin2hex(random_bytes(4))))->download();
    }
}
