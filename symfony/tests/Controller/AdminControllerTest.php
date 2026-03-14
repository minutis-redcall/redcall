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
            $container->get('security.password_encoder')
        );
    }

    private function getCsrfToken($container) : string
    {
        /** @var CsrfTokenManagerInterface $tokenManager */
        $tokenManager = $container->get('security.csrf.token_manager');

        return $tokenManager->getToken('password_login')->getValue();
    }

    public function testListAndSearch()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin = $fixtures->createRawUser('admin_list@example.com', 'password', true);
        $user  = $fixtures->createRawUser('search_me@example.com', 'password');

        $this->login($client, $admin);

        // 1. Test List
        $crawler = $client->request('GET', '/admin/users/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('table', 'admin_list@example.com');
        $this->assertSelectorTextContains('table', 'search_me@example.com');

        // 2. Test Search
        $form                   = $crawler->filter('form')->form();
        $form['form[criteria]'] = 'search_me';
        $crawler                = $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('table', 'search_me@example.com');
        $this->assertSelectorTextNotContains('table', 'admin_list@example.com');
    }

    public function testPermissionToggles()
    {
        $client = static::createClient();
        $client->followRedirects();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin  = $fixtures->createRawUser('admin_toggles@example.com', 'password', true);
        $target = $fixtures->createRawUser('target@example.com', 'password', false);
        $target->setIsVerified(false);
        $target->setIsTrusted(false);
        $target->setIsAdmin(false);
        $client->getContainer()->get('doctrine')->getManager()->flush();

        $this->login($client, $admin);
        $csrf = $this->getCsrfToken($client->getContainer());

        $em = $client->getContainer()->get('doctrine')->getManager();

        // 1. Toggle Verify
        $client->request('GET', sprintf('/admin/users/toggle-verify/target@example.com/%s', $csrf));
        $this->assertResponseIsSuccessful();

        $em->clear();
        $userRepo   = $em->getRepository(User::class);
        $targetUser = $userRepo->findOneBy(['username' => 'target@example.com']);
        $this->assertTrue($targetUser->isVerified());

        // 2. Toggle Trust
        $client->request('GET', sprintf('/admin/users/toggle-trust/target@example.com/%s', $csrf));
        $this->assertResponseIsSuccessful();

        $em->clear();
        $targetUser = $userRepo->findOneBy(['username' => 'target@example.com']);
        $this->assertTrue($targetUser->isTrusted());

        // 3. Toggle Admin
        $client->request('GET', sprintf('/admin/users/toggle-admin/target@example.com/%s', $csrf));
        $this->assertResponseIsSuccessful();

        $em->clear();
        $targetUser = $userRepo->findOneBy(['username' => 'target@example.com']);
        $this->assertTrue($targetUser->isAdmin());
    }

    public function testDelete()
    {
        $client = static::createClient();
        $client->followRedirects();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin = $fixtures->createRawUser('admin_delete@example.com', 'password', true);
        $user  = $fixtures->createRawUser('to_delete@example.com', 'password');

        $this->login($client, $admin);
        $csrf = $this->getCsrfToken($client->getContainer());

        $client->request('GET', sprintf('/admin/users/delete/to_delete@example.com/%s', $csrf));
        $this->assertResponseIsSuccessful();

        $userRepo = $client->getContainer()->get('doctrine')->getRepository(User::class);
        $this->assertNull($userRepo->findOneBy(['username' => 'to_delete@example.com']));
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
