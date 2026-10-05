<?php

namespace App\Tests\Controller\Admin;

use App\Entity\User;
use App\Entity\UserAuditLog;
use App\Tests\Base\BaseWebTestCase;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class UserControllerTest extends BaseWebTestCase
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

        return $tokenManager->getToken('pegass')->getValue();
    }

    public function testPegassIndex()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin  = $fixtures->createRawUser('pegass_admin@test.com', 'password', true);
        $target = $fixtures->createRawUser('pegass_target@test.com', 'password', false);

        $this->login($client, $admin);

        $crawler = $client->request('GET', '/admin/redcall-users');

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

        $client->request('GET', sprintf('/admin/redcall-users/toggle-verify/%s/%s', $csrf, $target->getId()));
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

        $client->request('GET', sprintf('/admin/redcall-users/toggle-trust/%s/%s', $csrf, $target->getId()));
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

        $client->request('GET', sprintf('/admin/redcall-users/toggle-admin/%s/%s', $csrf, $target->getId()));
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

        $client->request('GET', sprintf('/admin/redcall-users/delete/%s/%s', $csrf, $target->getId()));
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

        $crawler = $client->request('GET', '/admin/redcall-users/create-user');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    private function submitCreateUser($client, string $externalId) : void
    {
        $crawler = $client->request('GET', '/admin/redcall-users/create-user');
        $form    = $crawler->filter('form[name="form"]')->form();
        $form['form[externalId]'] = $externalId;
        $client->submit($form);
    }

    public function testCreateUserTrustsExistingUntrustedUser()
    {
        // Non-reg: support "re-creates" a user who lost access, but the account
        // already existed, so creation silently failed and access stayed disabled.
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());
        $em       = $client->getContainer()->get('doctrine.orm.entity_manager');

        $admin  = $fixtures->createRawUser('create_trust_admin@test.com', 'password', true);
        $target = $fixtures->createRawUser('create_trust_target@test.com', 'password', false);
        $fixtures->createVolunteer($target, 'CREATETRUST1', 'create_trust_target@test.com');
        $target->setIsTrusted(false);
        $em->persist($target); // User is DEFERRED_EXPLICIT
        $em->flush();

        $this->login($client, $admin);
        $this->submitCreateUser($client, 'CREATETRUST1');
        $this->assertResponseRedirects();

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();
        $updated = $em->getRepository(User::class)->findOneBy(['username' => 'create_trust_target@test.com']);
        $this->assertTrue($updated->isTrusted());

        $logs = $em->getRepository(UserAuditLog::class)->findBy([
            'targetUsername' => 'create_trust_target@test.com',
            'action'         => 'update',
        ]);
        $this->assertCount(1, $logs);
        $this->assertSame('create_trust_admin@test.com', $logs[0]->getActor() ? $logs[0]->getActor()->getUsername() : null);
        $this->assertFalse($logs[0]->getSnapshot()['old']['isTrusted']);
        $this->assertTrue($logs[0]->getSnapshot()['new']['isTrusted']);

        $client->followRedirect();
        $this->assertSelectorTextContains('.flashes-container', 'réactivé');
    }

    public function testCreateUserLeavesTrustedExistingUserUntouched()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());
        $em       = $client->getContainer()->get('doctrine.orm.entity_manager');

        $admin  = $fixtures->createRawUser('create_exists_admin@test.com', 'password', true);
        $target = $fixtures->createRawUser('create_exists_target@test.com', 'password', false);
        $fixtures->createVolunteer($target, 'CREATEEXISTS1', 'create_exists_target@test.com');

        $this->login($client, $admin);
        $this->submitCreateUser($client, 'CREATEEXISTS1');
        $this->assertResponseRedirects();

        $client->followRedirect();
        $this->assertSelectorTextContains('.flashes-container', 'existe déjà');
    }

    public function testCreateUserCreatesTrustedUser()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());
        $em       = $client->getContainer()->get('doctrine.orm.entity_manager');

        $admin = $fixtures->createRawUser('create_new_admin@test.com', 'password', true);
        $fixtures->createStandaloneVolunteer('CREATENEW1', 'create_new_target@test.com');

        $this->login($client, $admin);
        $this->submitCreateUser($client, 'CREATENEW1');
        $this->assertResponseRedirects();

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();
        $created = $em->getRepository(User::class)->findOneBy(['externalId' => 'CREATENEW1']);
        $this->assertNotNull($created);
        $this->assertTrue($created->isTrusted());
    }

    public function testIndexShowsAccessBanners()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());
        $em       = $client->getContainer()->get('doctrine.orm.entity_manager');

        $admin  = $fixtures->createRawUser('banner_admin@test.com', 'password', true);
        $target = $fixtures->createRawUser('banner_target@test.com', 'password', false, false);
        $target->setIsTrusted(false);
        $em->persist($target); // User is DEFERRED_EXPLICIT
        $em->flush();

        $this->login($client, $admin);
        $client->request('GET', '/admin/redcall-users', ['form' => ['criteria' => 'banner_target']]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.alert-danger', 'Accès bloqué : adresse email non vérifiée.');
        $this->assertSelectorTextContains('.alert-warning', 'Accès actuellement désactivé');
    }

    public function testIndexShowsNoBannerForHealthyUser()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin = $fixtures->createRawUser('healthy_admin@test.com', 'password', true);
        $fixtures->createUserWithStructure('healthy_target@test.com');

        $this->login($client, $admin);
        $client->request('GET', '/admin/redcall-users', ['form' => ['criteria' => 'healthy_target']]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('.card .alert');
    }
}
