<?php

namespace App\Tests\Security;

use App\Security\CronTokenVerifier;
use Google\Auth\AccessToken;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CronTokenVerifierTest extends TestCase
{
    private $originalWebsiteUrl;
    private $originalInvokerSa;

    protected function setUp() : void
    {
        parent::setUp();

        $this->originalWebsiteUrl = getenv('WEBSITE_URL');
        $this->originalInvokerSa  = getenv('CRON_INVOKER_SA');

        putenv('WEBSITE_URL=https://dev.redcall.example');
        putenv('CRON_INVOKER_SA=cron-invoker@redcall-dev.iam.gserviceaccount.com');
    }

    protected function tearDown() : void
    {
        putenv(false === $this->originalWebsiteUrl ? 'WEBSITE_URL' : 'WEBSITE_URL='.$this->originalWebsiteUrl);
        putenv(false === $this->originalInvokerSa ? 'CRON_INVOKER_SA' : 'CRON_INVOKER_SA='.$this->originalInvokerSa);

        parent::tearDown();
    }

    public function testAcceptsTokenFromTheInvokerServiceAccount()
    {
        $verifier = new CronTokenVerifier($this->createAccessToken([
            'email'          => 'cron-invoker@redcall-dev.iam.gserviceaccount.com',
            'email_verified' => true,
        ]));

        $this->assertTrue($verifier->verify('some-token'));
    }

    public function testRejectsWhenGoogleRefusesTheToken()
    {
        $verifier = new CronTokenVerifier($this->createAccessToken(false));

        $this->assertFalse($verifier->verify('some-token'));
    }

    public function testRejectsTokenFromAnotherServiceAccount()
    {
        $verifier = new CronTokenVerifier($this->createAccessToken([
            'email'          => 'evil@attacker.iam.gserviceaccount.com',
            'email_verified' => true,
        ]));

        $this->assertFalse($verifier->verify('some-token'));
    }

    public function testRejectsUnverifiedEmailClaim()
    {
        $verifier = new CronTokenVerifier($this->createAccessToken([
            'email'          => 'cron-invoker@redcall-dev.iam.gserviceaccount.com',
            'email_verified' => false,
        ]));

        $this->assertFalse($verifier->verify('some-token'));
    }

    public function testRejectsWhenInvokerIsNotConfigured()
    {
        putenv('CRON_INVOKER_SA');

        $verifier = new CronTokenVerifier($this->createAccessToken([
            'email'          => 'cron-invoker@redcall-dev.iam.gserviceaccount.com',
            'email_verified' => true,
        ]));

        $this->assertFalse($verifier->verify('some-token'));
    }

    /**
     * @param array|false $payload what AccessToken::verify() should return
     */
    private function createAccessToken($payload) : AccessToken
    {
        $accessToken = $this->createMock(AccessToken::class);

        $accessToken->method('verify')->willReturnCallback(
            function (string $token, array $options) use ($payload) {
                // The verifier must pin audience and issuer.
                $this->assertSame('https://dev.redcall.example', $options['audience'] ?? null);
                $this->assertSame('https://accounts.google.com', $options['issuer'] ?? null);

                return $payload;
            }
        );

        return $accessToken;
    }
}
