<?php

namespace App\Tests\Entity;

use App\Entity\Pegass;
use PHPUnit\Framework\TestCase;

class PegassTest extends TestCase
{
    public function testEvaluateReturnsNullWhenContentIsNull(): void
    {
        $pegass = new Pegass();
        $this->assertNull($pegass->evaluate('[some][path]'));
    }

    public function testEvaluateReturnsValueFromContent(): void
    {
        $pegass = new Pegass();
        $pegass->setContent([
            'user' => [
                'name' => 'John Doe',
                'age' => 30,
            ],
        ]);

        $this->assertSame('John Doe', $pegass->evaluate('[user][name]'));
        $this->assertSame(30, $pegass->evaluate('[user][age]'));
    }

    public function testEvaluateReturnsArrayForNestedContent(): void
    {
        $pegass = new Pegass();
        $pegass->setContent([
            'data' => [
                'items' => ['a', 'b', 'c'],
            ],
        ]);

        $this->assertSame(['a', 'b', 'c'], $pegass->evaluate('[data][items]'));
    }

    public function testEvaluateReturnsNullForInvalidPath(): void
    {
        $pegass = new Pegass();
        $pegass->setContent(['key' => 'value']);

        $this->assertNull($pegass->evaluate('[nonexistent][path]'));
    }

    public function testGetXmlConvertsContentToFormattedXml(): void
    {
        $pegass = new Pegass();
        $pegass->setType(Pegass::TYPE_VOLUNTEER);
        $pegass->setContent([
            'name' => 'John',
            'age' => '30',
        ]);

        $xml = $pegass->getXml();

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('<volunteer>', $xml);
        $this->assertStringContainsString('<name>John</name>', $xml);
        $this->assertStringContainsString('<age>30</age>', $xml);
        $this->assertStringContainsString('</volunteer>', $xml);
    }

    public function testXpathFindsNodes(): void
    {
        $pegass = new Pegass();
        $pegass->setType(Pegass::TYPE_STRUCTURE);
        $pegass->setContent([
            'name' => 'Test Structure',
            'code' => '12345',
        ]);

        $result = $pegass->xpath('//name');

        $this->assertCount(1, $result);
        $this->assertSame(['name' => 'Test Structure'], $result[0]);
    }

    public function testXpathWithParameterSubstitution(): void
    {
        $pegass = new Pegass();
        $pegass->setType(Pegass::TYPE_STRUCTURE);
        $pegass->setContent([
            'items' => [
                ['label' => 'Alpha'],
                ['label' => 'Beta'],
            ],
        ]);

        $result = $pegass->xpath('//label[text()={0}]', ['Alpha']);

        $this->assertCount(1, $result);
        $this->assertSame(['label' => 'Alpha'], $result[0]);
    }

    public function testXpathQuoteWithNoQuotes(): void
    {
        $pegass = new Pegass();

        $this->assertSame('"hello world"', $pegass->xpathQuote('hello world'));
    }

    public function testXpathQuoteWithDoubleQuotes(): void
    {
        $pegass = new Pegass();

        // Contains double quotes, so should wrap in single quotes
        $this->assertSame('\'say "hello"\'', $pegass->xpathQuote('say "hello"'));
    }

    public function testXpathQuoteWithSingleQuotes(): void
    {
        $pegass = new Pegass();

        // Contains single quotes only, should wrap in double quotes
        $this->assertSame('"it\'s fine"', $pegass->xpathQuote("it's fine"));
    }

    public function testXpathQuoteWithBothQuoteTypes(): void
    {
        $pegass = new Pegass();

        // Contains both single and double quotes, should use concat()
        $result = $pegass->xpathQuote('say "it\'s"');
        $this->assertStringStartsWith('concat(', $result);
        $this->assertStringEndsWith(')', $result);
        $this->assertStringContainsString("'\"'", $result);
    }

    public function testPrePersistSetsCreatedAtAndUpdatedAt(): void
    {
        $pegass = new Pegass();
        $this->assertNull($pegass->getCreatedAt());
        $this->assertNull($pegass->getUpdatedAt());

        $pegass->prePersist();

        $this->assertInstanceOf(\DateTimeImmutable::class, $pegass->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $pegass->getUpdatedAt());
        $this->assertEqualsWithDelta(time(), $pegass->getCreatedAt()->getTimestamp(), 2);
        $this->assertEqualsWithDelta(time(), $pegass->getUpdatedAt()->getTimestamp(), 2);
    }

    public function testGetXmlWithEmptyContent(): void
    {
        $pegass = new Pegass();
        $pegass->setType(Pegass::TYPE_VOLUNTEER);
        $pegass->setContent(['empty' => []]);

        $xml = $pegass->getXml();

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('<volunteer>', $xml);
    }

    public function testGetXmlWithNestedArrayContent(): void
    {
        $pegass = new Pegass();
        $pegass->setType(Pegass::TYPE_VOLUNTEER);
        $pegass->setContent([
            'skills' => [
                ['name' => 'First Aid'],
                ['name' => 'Logistics'],
            ],
        ]);

        $xml = $pegass->getXml();

        $this->assertStringContainsString('<name>First Aid</name>', $xml);
        $this->assertStringContainsString('<name>Logistics</name>', $xml);
    }

    public function testContentJsonRoundTrip(): void
    {
        $pegass = new Pegass();
        $data = ['key' => 'value', 'nested' => ['a' => 1, 'b' => 2]];

        $pegass->setContent($data);
        $this->assertSame($data, $pegass->getContent());
    }

    public function testContentNullRoundTrip(): void
    {
        $pegass = new Pegass();
        $pegass->setContent(null);
        $this->assertNull($pegass->getContent());
    }

    public function testGetXmlWithValueZero(): void
    {
        $pegass = new Pegass();
        $pegass->setType(Pegass::TYPE_STRUCTURE);
        $pegass->setContent([
            'count' => '0',
        ]);

        $xml = $pegass->getXml();

        // Value '0' should still appear (not treated as empty)
        $this->assertStringContainsString('<count>0</count>', $xml);
    }
}
