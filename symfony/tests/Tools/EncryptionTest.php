<?php

namespace App\Tests\Tools;

use App\Tools\Encryption;
use PHPUnit\Framework\TestCase;

class EncryptionTest extends TestCase
{
    private string $originalSecret;

    protected function setUp(): void
    {
        $this->originalSecret = getenv('SECRET') ?: '';
        putenv('SECRET=test-secret-key-for-unit-tests');
    }

    protected function tearDown(): void
    {
        putenv('SECRET=' . $this->originalSecret);
    }

    public function testEncryptReturnsVersionedString(): void
    {
        $encrypted = Encryption::encrypt('hello');

        $this->assertStringStartsWith('1-', $encrypted);
    }

    public function testEncryptProducesUrlSafeOutput(): void
    {
        $encrypted = Encryption::encrypt('some plaintext data');

        // After the version prefix, should be URL-safe base64
        $payload = substr($encrypted, 2);
        $this->assertDoesNotMatchRegularExpression('/[+\/=]/', $payload);
    }

    public function testDecryptReversesEncrypt(): void
    {
        $plaintext = 'Hello, World! This is a test message.';
        $encrypted = Encryption::encrypt($plaintext);
        $decrypted = Encryption::decrypt($encrypted);

        $this->assertSame($plaintext, $decrypted);
    }

    public function testEncryptDecryptWithSalt(): void
    {
        $plaintext = 'secret data';
        $salt = 'my-salt-value';

        $encrypted = Encryption::encrypt($plaintext, $salt);
        $decrypted = Encryption::decrypt($encrypted, $salt);

        $this->assertSame($plaintext, $decrypted);
    }

    public function testEncryptWithDifferentSaltsProducesDifferentCiphertexts(): void
    {
        $plaintext = 'same plaintext';
        $encrypted1 = Encryption::encrypt($plaintext, 'salt1');
        $encrypted2 = Encryption::encrypt($plaintext, 'salt2');

        $this->assertNotSame($encrypted1, $encrypted2);
    }

    public function testDecryptWithWrongSaltFails(): void
    {
        $encrypted = Encryption::encrypt('secret', 'correct-salt');

        $this->expectException(\LogicException::class);
        Encryption::decrypt($encrypted, 'wrong-salt');
    }

    public function testDecryptWithInvalidVersionThrows(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Invalid or missing encryption version');

        Encryption::decrypt('99-someciphertext');
    }

    public function testEncryptDecryptEmptyStringThrows(): void
    {
        // Empty string encrypts fine, but decryption returns a falsy value,
        // which triggers the "Could not decrypt" exception in the code.
        $encrypted = Encryption::encrypt('');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Could not decrypt provided cypher');
        Encryption::decrypt($encrypted);
    }

    public function testEncryptDecryptSpecialCharacters(): void
    {
        $plaintext = "Hé! ça va? Où est l'école? 日本語テスト";
        $encrypted = Encryption::encrypt($plaintext);
        $decrypted = Encryption::decrypt($encrypted);

        $this->assertSame($plaintext, $decrypted);
    }

    public function testEncryptDecryptLongString(): void
    {
        $plaintext = str_repeat('A long repeated string. ', 100);
        $encrypted = Encryption::encrypt($plaintext);
        $decrypted = Encryption::decrypt($encrypted);

        $this->assertSame($plaintext, $decrypted);
    }
}
