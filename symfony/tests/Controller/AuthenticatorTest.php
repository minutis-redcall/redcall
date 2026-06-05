<?php

namespace App\Tests\Controller;

use App\Tests\Base\BaseWebTestCase;
use App\Tests\Fixtures\DataFixtures;
use Bundles\PasswordLoginBundle\Manager\CaptchaManager;
use Bundles\SandboxBundle\Manager\FakeEmailManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * End-to-end tests for the security authenticators.
 *
 * Unlike the rest of the suite which forges an authenticated token via
 * KernelBrowser::loginUser() and skips the authenticator pipeline entirely,
 * these tests drive the *real* HTTP login flow:
 *   - GET the login form, POST it, follow redirects, then hit a protected
 *     route and assert it doesn't bounce back to /connect.
 *
 * They are the regression net for the Sf6 authenticator rewrite.
 */
class AuthenticatorTest extends BaseWebTestCase
{
    private function getFixtures($container) : DataFixtures
    {
        return new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_hasher')
        );
    }

    private function whitelistIp($container, string $ip = '127.0.0.1') : void
    {
        $container->get(CaptchaManager::class)->whitelistNow($ip);
    }

    /**
     * Asserts the client is currently authenticated by hitting a route that
     * requires ROLE_USER (/profile) — without following redirects, so a 302
     * back to /connect proves we are NOT authenticated.
     */
    private function assertAuthenticated(KernelBrowser $client) : void
    {
        $client->request('GET', '/profile');

        $status = $client->getResponse()->getStatusCode();
        $this->assertSame(
            200,
            $status,
            sprintf(
                'Expected /profile to return 200 (authenticated), got %d (location: %s)',
                $status,
                $client->getResponse()->headers->get('Location') ?: 'n/a'
            )
        );
    }

    private function assertNotAuthenticated(KernelBrowser $client) : void
    {
        $client->request('GET', '/profile');

        // Unauthenticated request to a ROLE_USER route is bounced by the
        // entry_point (Minutis) which redirects to /connect.
        $this->assertTrue(
            $client->getResponse()->isRedirect(),
            sprintf(
                'Expected /profile to redirect for an unauthenticated client, got %d',
                $client->getResponse()->getStatusCode()
            )
        );
        $location = $client->getResponse()->headers->get('Location');
        $this->assertStringContainsString(
            '/connect',
            $location,
            sprintf('Expected redirect to /connect, got %s', $location)
        );
    }

    // ─────────────────────────────────────────────
    // FormLogin authenticator (email + password)
    // ─────────────────────────────────────────────

    public function testFormLoginSuccess() : void
    {
        $client    = static::createClient();
        $container = $client->getContainer();
        $this->whitelistIp($container);

        $this->getFixtures($container)->createRawUser(
            'formlogin_ok@test.com',
            'StrongPassword123!',
            false,
            true
        );

        $client->followRedirects();
        $crawler = $client->request('GET', '/connect');
        $this->assertResponseIsSuccessful();

        $form             = $crawler->filter('#classic-login form')->form();
        $form['username'] = 'formlogin_ok@test.com';
        $form['password'] = 'StrongPassword123!';
        $client->submit($form);

        $this->assertResponseIsSuccessful();

        // Drop redirect-following, then prove session carries an authed user.
        $client->followRedirects(false);
        $this->assertAuthenticated($client);
    }

    public function testFormLoginWrongPassword() : void
    {
        $client    = static::createClient();
        $container = $client->getContainer();
        $this->whitelistIp($container);

        $this->getFixtures($container)->createRawUser(
            'formlogin_bad@test.com',
            'GoodPassword123!',
            false,
            true
        );

        $client->followRedirects();
        $crawler = $client->request('GET', '/connect');

        $form             = $crawler->filter('#classic-login form')->form();
        $form['username'] = 'formlogin_bad@test.com';
        $form['password'] = 'WrongPassword987!';
        $client->submit($form);

        // Failure flow lands back on /connect (via FormLoginAuthenticator's
        // onAuthenticationFailure → getLoginUrl()).
        $this->assertStringContainsString('/connect', $client->getRequest()->getRequestUri());

        $client->followRedirects(false);
        $this->assertNotAuthenticated($client);
    }

    public function testFormLoginUnverifiedUserCannotConnect() : void
    {
        $client    = static::createClient();
        $container = $client->getContainer();
        $this->whitelistIp($container);

        $this->getFixtures($container)->createRawUser(
            'formlogin_unverified@test.com',
            'StrongPassword123!',
            false,
            false /* verified=false */
        );

        $client->followRedirects();
        $crawler = $client->request('GET', '/connect');

        $form             = $crawler->filter('#classic-login form')->form();
        $form['username'] = 'formlogin_unverified@test.com';
        $form['password'] = 'StrongPassword123!';
        $client->submit($form);

        // Authenticator clears the token and redirects back to /connect when
        // user is not yet verified.
        $this->assertStringContainsString('/connect', $client->getRequest()->getRequestUri());

        $client->followRedirects(false);
        $this->assertNotAuthenticated($client);
    }

    // ─────────────────────────────────────────────
    // Nivol authenticator (NIVOL → email OTP → /code)
    // ─────────────────────────────────────────────

    public function testNivolLoginSuccess() : void
    {
        $client    = static::createClient();
        $container = $client->getContainer();
        $this->whitelistIp($container);

        $fixtures = $this->getFixtures($container);
        $user     = $fixtures->createRawUser('nivol_ok@test.com', 'pwd', false, true);
        // NivolManager::getUserByNivol does ltrim($nivol, '0') before
        // findOneByExternalId(), so volunteer rows store the stripped form.
        $fixtures->createVolunteer($user, '99112233', 'nivol_ok@test.com');

        // Step 1 — submit NIVOL form, expect a redirect to /code/{uuid}.
        $client->followRedirects(false);
        $crawler = $client->request('GET', '/nivol');
        $this->assertResponseIsSuccessful();

        $form                 = $crawler->filter('form[name="nivol"], form')->first()->form();
        $form['nivol[nivol]'] = '00099112233';
        $client->submit($form);

        $this->assertTrue(
            $client->getResponse()->isRedirect(),
            'Expected redirect to /code/{uuid} after submitting NIVOL form'
        );
        $codeUrl = $client->getResponse()->headers->get('Location');
        $this->assertMatchesRegularExpression('#/code/[a-f0-9-]+#', $codeUrl);

        // Step 2 — read the OTP from the fake email inbox.
        $emails = $container->get(FakeEmailManager::class)
            ->findMessagesForEmail('nivol_ok@test.com');
        $this->assertNotEmpty($emails, 'OTP email should have been sent');
        $body = $emails[0]->getBody();

        // Code format: 1 upper + 2 digits + 1 upper + 2 digits, wrapped in <strong>.
        $this->assertMatchesRegularExpression('#<strong>([A-Z]\d{2}[A-Z]\d{2})</strong>#', $body);
        preg_match('#<strong>([A-Z]\d{2}[A-Z]\d{2})</strong>#', $body, $matches);
        $code = $matches[1];

        // Step 3 — submit the code form on /code/{uuid}.
        $client->followRedirects(true);
        $crawler = $client->request('GET', $codeUrl);
        $this->assertResponseIsSuccessful();

        $form         = $crawler->filter('form')->first()->form();
        $form['code'] = $code;
        $client->submit($form);

        $this->assertResponseIsSuccessful();

        // Step 4 — the session should now carry an authenticated user.
        $client->followRedirects(false);
        $this->assertAuthenticated($client);
    }

    public function testNivolLoginWrongCode() : void
    {
        $client    = static::createClient();
        $container = $client->getContainer();
        $this->whitelistIp($container);

        $fixtures = $this->getFixtures($container);
        $user     = $fixtures->createRawUser('nivol_bad@test.com', 'pwd', false, true);
        $fixtures->createVolunteer($user, '99112299', 'nivol_bad@test.com');

        $client->followRedirects(false);
        $crawler = $client->request('GET', '/nivol');

        $form                 = $crawler->filter('form')->first()->form();
        $form['nivol[nivol]'] = '00099112299';
        $client->submit($form);

        $codeUrl = $client->getResponse()->headers->get('Location');

        $client->followRedirects(false);
        $crawler = $client->request('GET', $codeUrl);
        $this->assertResponseIsSuccessful();

        $form         = $crawler->filter('form')->first()->form();
        $form['code'] = 'X99X99'; // not the issued code
        $client->submit($form);

        $this->assertNotAuthenticated($client);
    }

    public function testNivolLoginUnknownNivolStaysOnForm() : void
    {
        $client    = static::createClient();
        $container = $client->getContainer();
        $this->whitelistIp($container);

        $client->followRedirects(false);
        $crawler = $client->request('GET', '/nivol');
        $this->assertResponseIsSuccessful();

        $form                 = $crawler->filter('form')->first()->form();
        $form['nivol[nivol]'] = '00000000000'; // not in the DB
        $client->submit($form);

        // NivolType has a Callback constraint that surfaces a "nivol_not_found"
        // violation, so the form stays on /nivol with a 200 (re-rendered with
        // errors) and no email is sent.
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $emails = $container->get(FakeEmailManager::class)->findAllEmails();
        $this->assertEmpty($emails, 'No OTP email should be sent for an unknown NIVOL');
    }

    // ─────────────────────────────────────────────
    // Minutis authenticator
    // ─────────────────────────────────────────────
    //
    // The Minutis flow expects a JWT signed by an external service whose public
    // key is fetched at runtime from MINUTIS_JWT_PUBLIC_KEY_URL. Reproducing
    // that end-to-end requires either (a) running the external public-key
    // server in tests or (b) test-only DI overrides for the public-key fetch
    // and JWT decoding.
    //
    // Once the FormLogin/Nivol patterns are stable we'll add a Minutis test by
    // injecting a known keypair via services_test.yaml and signing a JWT with
    // the matching private key inside the test.
}
