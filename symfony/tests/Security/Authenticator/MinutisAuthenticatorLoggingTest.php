<?php

namespace App\Tests\Security\Authenticator;

use App\Manager\UserManager;
use App\Manager\VolunteerManager;
use App\Manager\VolunteerSessionManager;
use App\Security\Authenticator\MinutisAuthenticator;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Regression (CERT-FR RM #1080870, ANSSI-05): the SSO bearer token was
 * written to the logs (debug on receipt, warning on failure), which are
 * shipped to Google Cloud Logging and Slack.
 */
#[AllowMockObjectsWithoutExpectations]
class MinutisAuthenticatorLoggingTest extends TestCase
{
    public function testJwtIsNeverLogged(): void
    {
        $handler = new TestHandler();

        $authenticator = new MinutisAuthenticator(
            $this->createMock(VolunteerManager::class),
            $this->createMock(UserManager::class),
            $this->createMock(VolunteerSessionManager::class),
            $this->createMock(RouterInterface::class),
            new Logger('test', [$handler]),
            $this->createMock(KernelInterface::class),
            new RequestStack()
        );

        $jwt = 'eyJhbGciOiJSUzI1NiJ9.eyJuaXZvbCI6InNlY3JldCJ9.c2lnbmF0dXJlLXNlY3JldA';

        try {
            $authenticator->authenticate(Request::create('/auth', 'POST', ['jwt' => $jwt]));
            $this->fail('An undecodable JWT must be rejected');
        } catch (AuthenticationException $e) {
            // expected
        }

        $this->assertNotEmpty($handler->getRecords(), 'The failure should still be logged');
        foreach ($handler->getRecords() as $record) {
            $this->assertStringNotContainsString($jwt, json_encode($record->toArray()));
        }
    }
}
