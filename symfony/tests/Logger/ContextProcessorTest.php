<?php

namespace App\Tests\Logger;

use App\Logger\ContextProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ContextProcessorTest extends TestCase
{
    private function createProcessor(
        string $env = 'test',
        ?Request $request = null,
        ?UserInterface $user = null
    ): ContextProcessor {
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->method('getEnvironment')->willReturn($env);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        if ($user) {
            $token = $this->createMock(TokenInterface::class);
            $token->method('getUser')->willReturn($user);
            $tokenStorage->method('getToken')->willReturn($token);
        } else {
            $tokenStorage->method('getToken')->willReturn(null);
        }

        $requestStack = new RequestStack();
        if ($request) {
            $requestStack->push($request);
        }

        return new ContextProcessor($kernel, $tokenStorage, $requestStack);
    }

    private function createLogRecord(array $extra = []): LogRecord
    {
        return new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            message: 'Test message',
            level: Level::Info,
            extra: $extra,
        );
    }

    // --- env ---

    public function testAddsEnvironmentToExtra(): void
    {
        $processor = $this->createProcessor('prod');
        $record = $this->createLogRecord();

        $result = $processor($record);

        $this->assertSame('prod', $result->extra['env']);
    }

    public function testAddsTestEnvironment(): void
    {
        $processor = $this->createProcessor('test');
        $record = $this->createLogRecord();

        $result = $processor($record);

        $this->assertSame('test', $result->extra['env']);
    }

    // --- platform ---

    public function testAddsPlatformToExtra(): void
    {
        $processor = $this->createProcessor();
        $record = $this->createLogRecord();

        $result = $processor($record);

        $this->assertSame(php_sapi_name(), $result->extra['platform']);
    }

    // --- no request ---

    public function testDoesNotAddUriWhenNoRequest(): void
    {
        $processor = $this->createProcessor();
        $record = $this->createLogRecord();

        $result = $processor($record);

        $this->assertArrayNotHasKey('uri', $result->extra);
        $this->assertArrayNotHasKey('body', $result->extra);
        $this->assertArrayNotHasKey('user', $result->extra);
    }

    // --- with request ---

    public function testAddsUriWhenRequestExists(): void
    {
        $request = Request::create('http://example.com/test');
        $processor = $this->createProcessor('test', $request);
        $record = $this->createLogRecord();

        $result = $processor($record);

        $this->assertSame('http://example.com/test', $result->extra['uri']);
    }

    public function testAddsBodyWhenRequestHasContent(): void
    {
        $request = Request::create(
            'http://example.com/api',
            'POST',
            [],
            [],
            [],
            [],
            '{"key":"value"}'
        );
        $processor = $this->createProcessor('test', $request);
        $record = $this->createLogRecord();

        $result = $processor($record);

        $this->assertSame('{"key":"value"}', $result->extra['body']);
    }

    public function testDoesNotAddBodyWhenRequestHasNoContent(): void
    {
        $request = Request::create('http://example.com/test');
        $processor = $this->createProcessor('test', $request);
        $record = $this->createLogRecord();

        $result = $processor($record);

        $this->assertArrayNotHasKey('body', $result->extra);
    }

    // --- user ---

    public function testAddsUserWhenAuthenticated(): void
    {
        $user = new class implements UserInterface {
            public function getUserIdentifier(): string { return 'admin@example.com'; }
            public function getRoles(): array { return ['ROLE_USER']; }
            public function getPassword(): ?string { return null; }
            public function getSalt(): ?string { return null; }
            public function eraseCredentials(): void {}
            public function getUsername(): string { return 'admin@example.com'; }
        };

        $request = Request::create('http://example.com/test');
        $processor = $this->createProcessor('test', $request, $user);
        $record = $this->createLogRecord();

        $result = $processor($record);

        $this->assertSame('admin@example.com', $result->extra['user']);
    }

    public function testDoesNotAddUserWhenNotAuthenticated(): void
    {
        $request = Request::create('http://example.com/test');
        $processor = $this->createProcessor('test', $request, null);
        $record = $this->createLogRecord();

        $result = $processor($record);

        $this->assertArrayNotHasKey('user', $result->extra);
    }

    // --- preserves existing extra data ---

    public function testPreservesExistingExtraData(): void
    {
        $processor = $this->createProcessor();
        $record = $this->createLogRecord(['existing_key' => 'existing_value']);

        $result = $processor($record);

        $this->assertSame('existing_value', $result->extra['existing_key']);
        $this->assertArrayHasKey('env', $result->extra);
        $this->assertArrayHasKey('platform', $result->extra);
    }

    // --- returns LogRecord ---

    public function testReturnsLogRecordInstance(): void
    {
        $processor = $this->createProcessor();
        $record = $this->createLogRecord();

        $result = $processor($record);

        $this->assertInstanceOf(LogRecord::class, $result);
    }

    // --- does not mutate original ---

    public function testDoesNotMutateOriginalRecord(): void
    {
        $processor = $this->createProcessor();
        $record = $this->createLogRecord();
        $originalExtra = $record->extra;

        $result = $processor($record);

        // Original should be unchanged
        $this->assertSame($originalExtra, $record->extra);
        // New one has extra fields
        $this->assertArrayHasKey('env', $result->extra);
    }
}
