<?php

namespace App\Tests\Tools;

use App\Tools\GSM;
use PHPUnit\Framework\TestCase;

class GSMTest extends TestCase
{
    // --- isGSMCompatible ---
    // Note: isGSMCompatible declares return type 'string' but returns true/false,
    // so PHP coerces to '1' (truthy) and '' (falsy). We use truthiness assertions.

    public function testIsGSMCompatibleWithPureAscii(): void
    {
        $this->assertNotEmpty(GSM::isGSMCompatible('Hello World'));
    }

    public function testIsGSMCompatibleWithGSMSpecialChars(): void
    {
        $this->assertNotEmpty(GSM::isGSMCompatible('@$!#%&'));
    }

    public function testIsGSMCompatibleWithAccentedCharsInAlphabet(): void
    {
        // These are in the GSM alphabet
        $this->assertNotEmpty(GSM::isGSMCompatible('éèùìòàÉÇ'));
    }

    public function testIsGSMCompatibleReturnsFalseForNonGSMChars(): void
    {
        $this->assertEmpty(GSM::isGSMCompatible('Hello 中文'));
    }

    public function testIsGSMCompatibleReturnsFalseForAccentedCharsNotInGSM(): void
    {
        // 'ê' is not in the GSM alphabet
        $this->assertEmpty(GSM::isGSMCompatible('être'));
    }

    public function testIsGSMCompatibleWithEmptyString(): void
    {
        $this->assertNotEmpty(GSM::isGSMCompatible(''));
    }

    public function testIsGSMCompatibleWithNewline(): void
    {
        $this->assertNotEmpty(GSM::isGSMCompatible("Hello\nWorld"));
    }

    public function testIsGSMCompatibleWithEscapedChars(): void
    {
        // Euro sign, backslash, caret, pipe are in the GSM alphabet (escaped set)
        $this->assertNotEmpty(GSM::isGSMCompatible('€\\^|'));
    }

    // --- transliterate ---

    public function testTransliterateReplacesCarriageReturnLineFeed(): void
    {
        // \r\n should be replaced with \n
        $this->assertSame("hello\nworld", GSM::transliterate("hello\r\nworld"));
    }

    public function testTransliterateReplacesCarriageReturn(): void
    {
        // \r alone should be replaced with \n
        $this->assertSame("hello\nworld", GSM::transliterate("hello\rworld"));
    }

    public function testTransliterateReplacesEmDash(): void
    {
        $this->assertSame('a-b', GSM::transliterate('a—b'));
    }

    public function testTransliterateReplacesEnDash(): void
    {
        $this->assertSame('a-b', GSM::transliterate('a–b'));
    }

    public function testTransliterateReplacesCyrillicChars(): void
    {
        $this->assertSame('Moskva', GSM::transliterate('Москва'));
    }

    public function testTransliterateKeepsGSMAccentedChars(): void
    {
        // 'É' is in the GSM alphabet and is NOT in the transliteration table,
        // so it stays as-is. 'ê' IS transliterated to 'e'.
        $this->assertSame('École', GSM::transliterate('École'));
        $this->assertSame('etre', GSM::transliterate('être'));
    }

    public function testTransliterateCollapsesMultipleSpaces(): void
    {
        $this->assertSame('a b', GSM::transliterate('a   b'));
    }

    public function testTransliterateReplacesGuillemets(): void
    {
        $this->assertSame('"hello"', GSM::transliterate('«hello»'));
    }

    public function testTransliterateBracketsToParens(): void
    {
        $this->assertSame('(a) (b)', GSM::transliterate('[a] {b}'));
    }

    // --- enforceGSMAlphabet ---

    public function testEnforceGSMAlphabetKeepsGSMChars(): void
    {
        $this->assertSame('Hello World', GSM::enforceGSMAlphabet('Hello World'));
    }

    public function testEnforceGSMAlphabetReplacesNonGSMWithQuestionMark(): void
    {
        // Chinese characters are not in GSM and not transliterable
        $this->assertSame('Hello ??', GSM::enforceGSMAlphabet('Hello 中文'));
    }

    public function testEnforceGSMAlphabetTransliteratesBeforeReplacing(): void
    {
        // 'ê' -> transliterated to 'e', which is GSM-compatible
        $this->assertSame('etre', GSM::enforceGSMAlphabet('être'));
    }

    public function testEnforceGSMAlphabetWithEmptyString(): void
    {
        $this->assertSame('', GSM::enforceGSMAlphabet(''));
    }

    // --- getSMSParts ---

    public function testGetSMSPartsShortGSMMessage(): void
    {
        $message = 'Hello';

        $parts = GSM::getSMSParts($message);

        $this->assertCount(1, $parts);
        $this->assertSame('Hello', $parts[0]);
    }

    public function testGetSMSPartsSinglePartAtExactly160Chars(): void
    {
        $message = str_repeat('A', 160);

        $parts = GSM::getSMSParts($message);

        $this->assertCount(1, $parts);
    }

    public function testGetSMSPartsMultipartAt161Chars(): void
    {
        $message = str_repeat('A', 161);

        $parts = GSM::getSMSParts($message);

        $this->assertGreaterThan(1, count($parts));
    }

    public function testGetSMSPartsUnicodeSinglePartAt70Chars(): void
    {
        // Use a non-GSM char to force Unicode encoding
        $message = str_repeat('中', 70);

        $parts = GSM::getSMSParts($message);

        $this->assertCount(1, $parts);
    }

    public function testGetSMSPartsUnicodeMultipartAt71Chars(): void
    {
        $message = str_repeat('中', 71);

        $parts = GSM::getSMSParts($message);

        $this->assertGreaterThan(1, count($parts));
    }

    public function testGetSMSPartsEscapedCharsCountDouble(): void
    {
        // Euro sign is an escaped char, counts as 2 in GSM
        // 80 euro signs = 160 GSM chars -> single part
        $message = str_repeat('€', 80);

        $parts = GSM::getSMSParts($message);

        $this->assertCount(1, $parts);
    }

    public function testGetSMSPartsEscapedCharsExceedingSinglePart(): void
    {
        // 81 euro signs = 162 GSM chars -> multipart
        $message = str_repeat('€', 81);

        $parts = GSM::getSMSParts($message);

        $this->assertGreaterThan(1, count($parts));
    }

    public function testGetSMSPartsEmptyMessage(): void
    {
        $parts = GSM::getSMSParts('');

        $this->assertCount(1, $parts);
        $this->assertSame('', $parts[0]);
    }

    public function testGetSMSPartsLongMessageSplitsCorrectly(): void
    {
        // 306 regular chars = 2 parts of 153 each
        $message = str_repeat('A', 306);

        $parts = GSM::getSMSParts($message);

        $this->assertCount(2, $parts);
        $this->assertSame(153, strlen($parts[0]));
        $this->assertSame(153, strlen($parts[1]));
    }

    public function testGetSMSPartsUnicodeLongMessageSplitsAt67(): void
    {
        // 134 Unicode chars = 2 parts of 67 each
        $message = str_repeat('中', 134);

        $parts = GSM::getSMSParts($message);

        $this->assertCount(2, $parts);
    }
}
