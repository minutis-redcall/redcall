<?php

namespace App\Tests\Controller;

use App\Tests\Base\BaseWebTestCase;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Covers the PasswordLoginBundle routes (registration, profile,
 * change-password, admin user management) not already covered by
 * SecurityControllerTest and AuthenticatorTest.
 */
class PasswordLoginBundleTest extends BaseWebTestCase
{
    private function getFixtures($container) : DataFixtures
    {
        return new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_hasher')
        );
    }

    private function csrf($container, string $id = 'csrf'): string
    {
        /** @var CsrfTokenManagerInterface $manager */
        $manager = $container->get('security.csrf.token_manager');
        if (!$container->get('request_stack')->getMainRequest()) {
            $req = \Symfony\Component\HttpFoundation\Request::create('/');
            $req->setSession(new \Symfony\Component\HttpFoundation\Session\Session(new \Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage()));
            $container->get('request_stack')->push($req);
        }

        return $manager->getToken($id)->getValue();
    }

    // ──────────────────────────────────────────────
    // /guest (not trusted)
    // ──────────────────────────────────────────────

    public function testGuestPageRendersForUntrustedUser(): void
    {
        $client    = static::createClient();
        $container = $client->getContainer();
        $user      = $this->getFixtures($container)
                          ->createRawUser('pl-guest-'.uniqid().'@test.com', 'password', false, true);

        $em = $container->get('doctrine.orm.entity_manager');
        $user->setIsTrusted(false);
        $em->persist($user);
        $em->flush();

        $this->login($client, $user);
        $client->request('GET', '/guest');
        $this->assertResponseIsSuccessful();
    }

    // ──────────────────────────────────────────────
    // /logout
    // ──────────────────────────────────────────────

    public function testLogoutWithValidCsrfRedirects(): void
    {
        $client    = static::createClient();
        $container = $client->getContainer();
        $user      = $this->getFixtures($container)
                          ->createRawUser('pl-logout-'.uniqid().'@test.com', 'password');
        $this->login($client, $user);

        $logoutCsrf = $this->csrf($container, 'logout');
        $client->request('GET', '/logout?_csrf_token='.$logoutCsrf);

        // Symfony logout redirects to the configured target ("/" in security.yaml).
        $this->assertResponseStatusCodeSame(302);
    }

    // ──────────────────────────────────────────────
    // /admin/users/ — now a redirect to the RedCall admin (the legacy
    // list/toggle/delete actions were removed; admin_redcall_users_*
    // is the live surface).
    // ──────────────────────────────────────────────

    public function testAdminUsersListRedirectsToRedcallAdmin(): void
    {
        $client = static::createClient();
        $admin  = $this->getFixtures($client->getContainer())
                       ->createRawUser('pl-admin-list-'.uniqid().'@test.com', 'password', true);
        $this->login($client, $admin);

        $client->request('GET', '/admin/users/');
        $this->assertResponseRedirects('/admin/redcall-users');
    }

    public function testAdminUsersListForbiddenForNonAdmin(): void
    {
        $client = static::createClient();
        $user   = $this->getFixtures($client->getContainer())
                       ->createRawUser('pl-list-out-'.uniqid().'@test.com', 'password', false);
        $this->login($client, $user);

        $client->request('GET', '/admin/users/');
        $this->assertResponseStatusCodeSame(403);
    }

    // ──────────────────────────────────────────────
    // /admin/users/profile/{username}
    // ──────────────────────────────────────────────

    public function testAdminUserProfileRendersForAdmin(): void
    {
        $client    = static::createClient();
        $container = $client->getContainer();
        $fixtures  = $this->getFixtures($container);

        $admin  = $fixtures->createRawUser('pl-admin-prof-'.uniqid().'@test.com', 'password', true);
        $target = $fixtures->createRawUser('pl-target-'.uniqid().'@test.com', 'password');

        $this->login($client, $admin);
        $client->request('GET', '/admin/users/profile/'.$target->getUsername());

        $this->assertResponseIsSuccessful();
    }

    // ──────────────────────────────────────────────
    // /admin/users/reset-password/{username}/{csrf}
    // ──────────────────────────────────────────────

    public function testAdminResetPasswordBadCsrfReturns404(): void
    {
        $client    = static::createClient();
        $container = $client->getContainer();
        $fixtures  = $this->getFixtures($container);

        $admin  = $fixtures->createRawUser('pl-rp-admin-'.uniqid().'@test.com', 'password', true);
        $target = $fixtures->createRawUser('pl-rp-target-'.uniqid().'@test.com', 'password');

        $this->login($client, $admin);
        $client->request('GET', sprintf('/admin/users/reset-password/%s/bad', $target->getUsername()));

        $this->assertResponseStatusCodeSame(404);
    }
}
