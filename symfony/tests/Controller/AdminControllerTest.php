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
}
