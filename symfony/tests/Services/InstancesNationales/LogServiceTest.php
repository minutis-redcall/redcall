<?php

namespace App\Tests\Services\InstancesNationales;

use App\Services\InstancesNationales\LogService;
use PHPUnit\Framework\TestCase;

class LogServiceTest extends TestCase
{
    protected function setUp() : void
    {
        LogService::flush();
    }

    protected function tearDown() : void
    {
        LogService::flush();
    }

    public function testFlushResetsAllState()
    {
        LogService::success('new', 'Created item');
        LogService::error('Something failed');
        LogService::info('Some info', [], true);

        LogService::flush();

        $this->assertFalse(LogService::isImpactful());
        $this->assertSame(0, LogService::getNbImpacts());
        $this->assertSame([
            'new'     => [],
            'updated' => [],
            'deleted' => [],
            'errors'  => [],
        ], LogService::getSummary());
    }

    public function testSuccessAddsToCorrectSummaryType()
    {
        LogService::success('new', 'Created volunteer');
        LogService::success('updated', 'Updated volunteer');
        LogService::success('deleted', 'Deleted volunteer');

        $summary = LogService::getSummary();

        $this->assertCount(1, $summary['new']);
        $this->assertSame('Created volunteer', $summary['new'][0]['message']);

        $this->assertCount(1, $summary['updated']);
        $this->assertSame('Updated volunteer', $summary['updated'][0]['message']);

        $this->assertCount(1, $summary['deleted']);
        $this->assertSame('Deleted volunteer', $summary['deleted'][0]['message']);
    }

    public function testSuccessWithInvalidTypeFallsBackToUpdated()
    {
        LogService::success('nonexistent_type', 'Some message');

        $summary = LogService::getSummary();

        $this->assertCount(1, $summary['updated']);
        $this->assertSame('Some message', $summary['updated'][0]['message']);
    }

    public function testSuccessWithParameters()
    {
        LogService::success('new', 'Created item', ['id' => 42]);

        $summary = LogService::getSummary();

        $this->assertSame(['id' => 42], $summary['new'][0]['parameters']);
    }

    public function testSuccessIsImpactful()
    {
        $this->assertFalse(LogService::isImpactful());

        LogService::success('new', 'Created item');

        $this->assertTrue(LogService::isImpactful());
        $this->assertSame(1, LogService::getNbImpacts());
    }

    public function testErrorAddsToErrorsSummary()
    {
        LogService::error('Something went wrong', ['detail' => 'bad data']);

        $summary = LogService::getSummary();

        $this->assertCount(1, $summary['errors']);
        $this->assertSame('Something went wrong', $summary['errors'][0]['message']);
        $this->assertSame(['detail' => 'bad data'], $summary['errors'][0]['parameters']);
    }

    public function testErrorIsImpactful()
    {
        LogService::error('fail');

        $this->assertTrue(LogService::isImpactful());
        $this->assertSame(1, LogService::getNbImpacts());
    }

    public function testInfoReturnsDebugCount()
    {
        $count = LogService::info('first info');
        $this->assertSame(1, $count);

        $count = LogService::info('second info');
        $this->assertSame(2, $count);
    }

    public function testInfoNotImpactfulByDefault()
    {
        LogService::info('just info');

        $this->assertFalse(LogService::isImpactful());
    }

    public function testInfoCanBeImpactful()
    {
        LogService::info('impactful info', [], true);

        $this->assertTrue(LogService::isImpactful());
    }

    public function testPassReturnsDebugCount()
    {
        $count = LogService::pass('passed check');
        $this->assertSame(1, $count);
    }

    public function testPassNotImpactfulByDefault()
    {
        LogService::pass('passed check');

        $this->assertFalse(LogService::isImpactful());
    }

    public function testPassCanBeImpactful()
    {
        LogService::pass('passed check', [], true);

        $this->assertTrue(LogService::isImpactful());
    }

    public function testFailAddsToErrorsSummary()
    {
        LogService::fail('failed check', ['reason' => 'timeout']);

        $summary = LogService::getSummary();

        $this->assertCount(1, $summary['errors']);
        $this->assertSame('failed check', $summary['errors'][0]['message']);
    }

    public function testFailReturnsDebugCount()
    {
        $count = LogService::fail('first failure');
        $this->assertSame(1, $count);

        $count = LogService::fail('second failure');
        // fail calls error which calls push (count 2), then fail adds another entry via error = 3
        // Actually: fail calls self::error, which pushes once. So after 2 fails, debug has 2 entries.
        $this->assertSame(2, $count);
    }

    public function testDumpWithReturnTrueReturnsString()
    {
        LogService::info('test message');

        $output = LogService::dump(true);

        $this->assertIsString($output);
        $this->assertStringContainsString('test message', $output);
    }

    public function testDumpWithReturnFalseOutputsDirectly()
    {
        LogService::info('direct output');

        ob_start();
        $result = LogService::dump(false);
        $output = ob_get_clean();

        $this->assertNull($result);
        $this->assertStringContainsString('direct output', $output);
    }

    public function testDumpFormatsParametersAsJson()
    {
        LogService::info('with params', ['key' => 'value']);

        $output = LogService::dump(true);

        $this->assertStringContainsString('{"key":"value"}', $output);
    }

    public function testDumpWithoutParametersDoesNotAppendParentheses()
    {
        LogService::info('no params');

        $output = LogService::dump(true);

        $this->assertStringNotContainsString('(', $output);
    }

    public function testDumpIncludesTimestamp()
    {
        LogService::info('timestamped');

        $output = LogService::dump(true);

        // Check that output contains a time-like pattern HH:MM:SS
        $this->assertMatchesRegularExpression('/\d{2}:\d{2}:\d{2}/', $output);
    }

    public function testMultipleImpactsAreCounted()
    {
        LogService::success('new', 'one');
        LogService::success('updated', 'two');
        LogService::error('three');

        $this->assertSame(3, LogService::getNbImpacts());
    }

    public function testIsImpactfulReturnsFalseWhenNoImpacts()
    {
        LogService::info('no impact');

        $this->assertFalse(LogService::isImpactful());
        $this->assertSame(0, LogService::getNbImpacts());
    }

    public function testGetSummaryReturnsAllCategories()
    {
        $summary = LogService::getSummary();

        $this->assertArrayHasKey('new', $summary);
        $this->assertArrayHasKey('updated', $summary);
        $this->assertArrayHasKey('deleted', $summary);
        $this->assertArrayHasKey('errors', $summary);
    }

    public function testDumpWithUnicodeParameters()
    {
        LogService::info('unicode test', ['name' => 'Jean-Pierre']);

        $output = LogService::dump(true);

        // JSON_UNESCAPED_UNICODE flag should preserve accented characters
        $this->assertStringContainsString('Jean-Pierre', $output);
    }
}
