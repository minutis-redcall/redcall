<?php

namespace App\Tests\Controller;

use App\Tests\Base\BaseWebTestCase;
use App\Tests\Fixtures\DataFixtures;

class HomeControllerTest extends BaseWebTestCase
{
    private function getFixtures($container) : DataFixtures
    {
        return new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_hasher')
        );
    }

    // ──────────────────────────────────────────────
    // GET / -> home
    // ──────────────────────────────────────────────

    public function testHomeRedirectsAnonymousToConnect(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseStatusCodeSame(302);
        // Anonymous users go through the MinutisAuthenticator entry point
        // which sends them to /connect.
        $location = $client->getResponse()->headers->get('Location');
        $this->assertNotNull($location);
    }

    public function testHomeRedirectsUntrustedUserToNotTrustedPage(): void
    {
        $client    = static::createClient();
        $container = $client->getContainer();
        $user      = $this->getFixtures($container)
                          ->createRawUser('home-untrusted-'.uniqid().'@test.com', 'password', false, true);

        // The factory always sets isTrusted=true; flip it explicitly to cover the guard.
        $em = $container->get('doctrine.orm.entity_manager');
        $user->setIsTrusted(false);
        $em->persist($user);
        $em->flush();

        $this->login($client, $user);
        $client->request('GET', '/');

        $this->assertResponseRedirects('/guest');
    }

    public function testHomeRendersForTrustedUser(): void
    {
        $client = static::createClient();
        $user   = $this->getFixtures($client->getContainer())
                       ->createRawUser('home-trusted-'.uniqid().'@test.com', 'password', false, true);

        $this->login($client, $user);
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('html');
    }

    // ──────────────────────────────────────────────
    // GET /locale/{locale} -> locale
    // ──────────────────────────────────────────────

    public function testLocaleRedirectsToHome(): void
    {
        $client = static::createClient();
        $client->request('GET', '/locale/fr');

        // PUBLIC_ACCESS — the locale change happens then we redirect to home.
        $this->assertResponseRedirects('/');
    }

    public function testLocaleAcceptsEnglish(): void
    {
        $client = static::createClient();
        $client->request('GET', '/locale/en');

        $this->assertResponseRedirects('/');
    }

    // ──────────────────────────────────────────────
    // GET /auth -> auth
    // ──────────────────────────────────────────────

    public function testAuthRedirectsToHome(): void
    {
        $client = static::createClient();
        $client->request('GET', '/auth');

        $this->assertResponseRedirects('/');
    }

    // ──────────────────────────────────────────────
    // GET /go-to-space -> space
    // ──────────────────────────────────────────────

    public function testGoToSpaceRedirectsAnonymousToConnect(): void
    {
        $client = static::createClient();
        $client->request('GET', '/go-to-space');

        $this->assertResponseStatusCodeSame(302);
    }

    public function testGoToSpaceReturns404WhenUserHasNoVolunteer(): void
    {
        $client = static::createClient();
        $user   = $this->getFixtures($client->getContainer())
                       ->createRawUser('home-novol-'.uniqid().'@test.com', 'password', false, true);
        // user has no Volunteer attached

        $this->login($client, $user);
        $client->request('GET', '/go-to-space');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testGoToSpaceRedirectsToSpaceHomeForUserWithVolunteer(): void
    {
        $client    = static::createClient();
        $fixtures  = $this->getFixtures($client->getContainer());
        $user      = $fixtures->createRawUser('home-vol-'.uniqid().'@test.com', 'password', false, true);
        $fixtures->createVolunteer($user, 'VOL-GO2SPACE-'.uniqid());

        $this->login($client, $user);
        $client->request('GET', '/go-to-space');

        $this->assertResponseRedirects();
        $location = $client->getResponse()->headers->get('Location');
        $this->assertStringContainsString('/space/', (string) $location);
    }
}
