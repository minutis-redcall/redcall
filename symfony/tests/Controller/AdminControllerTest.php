<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Tests\Base\BaseWebTestCase;
use App\Tests\Fixtures\DataFixtures;
use Bundles\SandboxBundle\Manager\FakeEmailManager;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class AdminControllerTest extends BaseWebTestCase
{
    private function getFixtures($container) : DataFixtures
    {
        return new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_hasher')
        );
    }

    private function getCsrfToken($container) : string
    {
        /** @var CsrfTokenManagerInterface $tokenManager */
        $tokenManager = $container->get('security.csrf.token_manager');

        // Sf6: CSRF token storage needs a session in RequestStack
        if (!$container->get('request_stack')->getMainRequest()) {
            $req = \Symfony\Component\HttpFoundation\Request::create('/');
            $req->setSession(new \Symfony\Component\HttpFoundation\Session\Session(new \Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage()));
            $container->get('request_stack')->push($req);
        }

        return $tokenManager->getToken('password_login')->getValue();
    }

    public function testListRedirectsToRedcallAdmin()
    {
        // The legacy /admin/users/ list page now just redirects to the RedCall
        // admin (the actual list / toggle / delete actions moved to
        // admin_redcall_users_* in src/Controller/Admin/UserController.php).
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin = $fixtures->createRawUser('admin_list@example.com', 'password', true);
        $this->login($client, $admin);

        $client->request('GET', '/admin/users/');
        $this->assertResponseRedirects('/admin/redcall-users');
    }

    public function testResetPassword()
    {
        $client = static::createClient();
        $client->followRedirects();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin = $fixtures->createRawUser('admin_reset@example.com', 'password', true);
        $user  = $fixtures->createRawUser('target_reset@example.com', 'password');

        $this->login($client, $admin);
        $csrf = $this->getCsrfToken($client->getContainer());

        $client->request('GET', sprintf('/admin/users/reset-password/target_reset@example.com/%s', $csrf));
        $this->assertResponseIsSuccessful();

        $fakeEmailManager = $client->getContainer()->get(FakeEmailManager::class);
        $emails           = $fakeEmailManager->findMessagesForEmail('target_reset@example.com');
        $this->assertCount(1, $emails, 'Reset password email should be sent');
        $this->assertSelectorExists('.alert-success');
    }

    public function testProfileEditByAdmin()
    {
        $client = static::createClient();
        $client->followRedirects();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin = $fixtures->createRawUser('admin_profile@example.com', 'password', true);
        $user  = $fixtures->createRawUser('other_user@example.com', 'password');

        $this->login($client, $admin);

        $crawler = $client->request('GET', '/admin/users/profile/other_user@example.com');
        $this->assertResponseIsSuccessful();

        $form                      = $crawler->filter('form[name="profile"]')->form();
        $form['profile[username]'] = 'updated_user@example.com';

        $client->submit($form);
        $this->assertResponseIsSuccessful();

        $userRepo = $client->getContainer()->get('doctrine')->getRepository(User::class);
        $this->assertNotNull($userRepo->findOneBy(['username' => 'updated_user@example.com']));
        $this->assertSelectorExists('.alert-success');
    }

    public function testSecurityAccessDenied()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $user = $fixtures->createRawUser('regular_user@example.com', 'password');
        $this->login($client, $user);

        // Regular users should not be able to access admin users list
        // Note: Check if the route is protected by security.yaml or if it returns 403
        $client->request('GET', '/admin/users/');
        $this->assertTrue($client->getResponse()->isForbidden() || $client->getResponse()->isRedirect());
    }

    // Regression (CERT-FR RM #1080870, audit H7, paths A/B): a plain admin
    // could change a root's username to an address they control, then sign
    // in with Google or reset the root's password.

    private function createPlainAdminAndRoot($container): array
    {
        $fixtures = $this->getFixtures($container);
        $em       = $container->get('doctrine.orm.entity_manager');
        $suffix   = uniqid();

        $admin = $fixtures->createRawUser("h7_plain_admin_{$suffix}@example.com", 'password', false);
        $admin->setIsAdmin(true);
        $em->persist($admin);
        $em->flush();
        $root = $fixtures->createRawUser("h7_root_{$suffix}@example.com", 'password', true);

        return [$admin, $root];
    }

    public function testPlainAdminCannotOpenRootProfile()
    {
        $client          = static::createClient();
        [$admin, $root]  = $this->createPlainAdminAndRoot($client->getContainer());
        $this->login($client, $admin);

        $client->request('GET', '/admin/users/profile/'.$root->getUsername());
        $this->assertResponseStatusCodeSame(404);

        $client->request('POST', '/admin/users/profile/'.$root->getUsername(), [
            'profile' => ['username' => 'attacker@example.com'],
        ]);
        $this->assertResponseStatusCodeSame(404);

        $userRepo = $client->getContainer()->get('doctrine')->getRepository(User::class);
        $this->assertNull($userRepo->findOneBy(['username' => 'attacker@example.com']));
    }

    public function testPlainAdminCannotResetRootPassword()
    {
        $client          = static::createClient();
        $container       = $client->getContainer();
        [$admin, $root]  = $this->createPlainAdminAndRoot($container);
        $this->login($client, $admin);
        $csrf = $this->getCsrfToken($container);

        $client->request('GET', sprintf('/admin/users/reset-password/%s/%s', $root->getUsername(), $csrf));
        $this->assertResponseStatusCodeSame(404);

        $emails = $container->get(FakeEmailManager::class)->findMessagesForEmail($root->getUsername());
        $this->assertCount(0, $emails, 'No reset email may be sent for a root on a plain admin request');
    }
}
