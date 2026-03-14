<?php

namespace App\Tests\Controller\Admin;

use App\Entity\User;
use App\Tests\Base\BaseWebTestCase;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class PegassControllerTest extends BaseWebTestCase
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

        return $tokenManager->getToken('pegass')->getValue();
    }

    public function testPegassIndex()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin  = $fixtures->createRawUser('pegass_admin@test.com', 'password', true);
        $target = $fixtures->createRawUser('pegass_target@test.com', 'password', false);

        $this->login($client, $admin);

        $crawler = $client->request('GET', '/admin/pegass');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'pegass_target@test.com');
    }

    public function testPegassToggleVerify()
    {
        $client = static::createClient();
        $client->followRedirects();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin  = $fixtures->createRawUser('pegass_verify_admin@test.com', 'password', true);
        $target = $fixtures->createRawUser('pegass_verify_target@test.com', 'password', false);
        $target->setIsVerified(false);
        $client->getContainer()->get('doctrine.orm.entity_manager')->flush();

        $this->login($client, $admin);
        $csrf = $this->getCsrfToken($client->getContainer());

        $client->request('GET', sprintf('/admin/pegass/toggle-verify/%s/%s', $csrf, $target->getId()));
        $this->assertResponseIsSuccessful();

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();
        $updatedTarget = $em->getRepository(User::class)->findOneBy(['username' => 'pegass_verify_target@test.com']);
        $this->assertTrue($updatedTarget->isVerified());
    }

    public function testPegassToggleTrust()
    {
        $client = static::createClient();
        $client->followRedirects();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin  = $fixtures->createRawUser('pegass_trust_admin@test.com', 'password', true);
        $target = $fixtures->createRawUser('pegass_trust_target@test.com', 'password', false);
        $target->setIsTrusted(false);
        $client->getContainer()->get('doctrine.orm.entity_manager')->flush();

        $this->login($client, $admin);
        $csrf = $this->getCsrfToken($client->getContainer());

        $client->request('GET', sprintf('/admin/pegass/toggle-trust/%s/%s', $csrf, $target->getId()));
        $this->assertResponseIsSuccessful();

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();
        $updatedTarget = $em->getRepository(User::class)->findOneBy(['username' => 'pegass_trust_target@test.com']);
        $this->assertTrue($updatedTarget->isTrusted());
    }

    public function testPegassToggleAdmin()
    {
        $client = static::createClient();
        $client->followRedirects();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin  = $fixtures->createRawUser('pegass_tadmin_admin@test.com', 'password', true);
        $target = $fixtures->createRawUser('pegass_tadmin_target@test.com', 'password', false);
        $target->setIsAdmin(false);
        $client->getContainer()->get('doctrine.orm.entity_manager')->flush();

        $this->login($client, $admin);
        $csrf = $this->getCsrfToken($client->getContainer());

        $client->request('GET', sprintf('/admin/pegass/toggle-admin/%s/%s', $csrf, $target->getId()));
        $this->assertResponseIsSuccessful();

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();
        $updatedTarget = $em->getRepository(User::class)->findOneBy(['username' => 'pegass_tadmin_target@test.com']);
        $this->assertTrue($updatedTarget->isAdmin());
    }

    public function testPegassDeleteUser()
    {
        $client = static::createClient();
        $client->followRedirects();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin  = $fixtures->createRawUser('pegass_del_admin@test.com', 'password', true);
        $target = $fixtures->createRawUser('pegass_del_target@test.com', 'password', false);

        $this->login($client, $admin);
        $csrf = $this->getCsrfToken($client->getContainer());

        $client->request('GET', sprintf('/admin/pegass/delete/%s/%s', $csrf, $target->getId()));
        $this->assertResponseIsSuccessful();

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();
        $deletedUser = $em->getRepository(User::class)->findOneBy(['username' => 'pegass_del_target@test.com']);
        $this->assertNull($deletedUser);
    }

    public function testPegassCreateUser()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin = $fixtures->createRawUser('pegass_create_admin@test.com', 'password', true);

        $this->login($client, $admin);

        $crawler = $client->request('GET', '/admin/pegass/create-user');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }
}
