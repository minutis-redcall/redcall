<?php

namespace App\Tests\Controller\Management;

use App\Entity\Structure;
use App\Tests\Base\BaseWebTestCase;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class StructuresControllerTest extends BaseWebTestCase
{
    private function getFixtures($container) : DataFixtures
    {
        return new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_hasher')
        );
    }

    private function getCsrfToken($container, string $tokenId = 'token') : string
    {
        /** @var CsrfTokenManagerInterface $tokenManager */
        $tokenManager = $container->get('security.csrf.token_manager');

        // Sf6: CSRF token storage needs a session in RequestStack
        if (!$container->get('request_stack')->getMainRequest()) {
            $req = \Symfony\Component\HttpFoundation\Request::create('/');
            $req->setSession(new \Symfony\Component\HttpFoundation\Session\Session(new \Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage()));
            $container->get('request_stack')->push($req);
        }

        return $tokenManager->getToken($tokenId)->getValue();
    }

    public function testListStructures()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin     = $fixtures->createRawUser('struct_list@example.com', 'password', true);
        $structure = $fixtures->createStructure('MY TEST STRUCTURE', 'EXT-LIST-001');
        $fixtures->assignUserToStructure($admin, $structure);

        $this->login($client, $admin);

        $crawler = $client->request('GET', '/management/structures/');
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString(
            'MY TEST STRUCTURE',
            $client->getResponse()->getContent()
        );
    }

    public function testListStructuresSearch()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin      = $fixtures->createRawUser('struct_search@example.com', 'password', true);
        $structureA = $fixtures->createStructure('ALPHA STRUCTURE', 'EXT-SEARCH-A');
        $structureB = $fixtures->createStructure('BETA STRUCTURE', 'EXT-SEARCH-B');
        $fixtures->assignUserToStructure($admin, $structureA);
        $fixtures->assignUserToStructure($admin, $structureB);

        $this->login($client, $admin);

        $crawler = $client->request('GET', '/management/structures/');
        $this->assertResponseIsSuccessful();

        // Submit the search form filtering for "ALPHA"
        $form                   = $crawler->filter('form')->form();
        $form['form[criteria]'] = 'ALPHA';
        $crawler                = $client->submit($form);

        $this->assertResponseIsSuccessful();
        $responseContent = $client->getResponse()->getContent();
        $this->assertStringContainsString('ALPHA STRUCTURE', $responseContent);
        $this->assertStringNotContainsString('BETA STRUCTURE', $responseContent);
    }

    public function testCreateStructure()
    {
        $client = static::createClient();
        $client->followRedirects();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin = $fixtures->createRawUser('struct_create@example.com', 'password', true);
        $this->login($client, $admin);

        $crawler = $client->request('GET', '/management/structures/create');
        $this->assertResponseIsSuccessful();

        $form                    = $crawler->filter('form[name="structure"]')->form();
        $form['structure[name]'] = 'NEWLY CREATED STRUCTURE';

        $client->submit($form);
        $this->assertResponseIsSuccessful();

        $em = $client->getContainer()->get('doctrine')->getManager();
        $em->clear();

        $structureRepo = $em->getRepository(Structure::class);
        $created       = $structureRepo->findOneBy(['name' => 'NEWLY CREATED STRUCTURE']);
        $this->assertNotNull($created, 'Structure should be created in the database');
    }

    public function testToggleLockStructure()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin     = $fixtures->createRawUser('struct_lock@example.com', 'password', true);
        $structure = $fixtures->createStructure('LOCK ME STRUCTURE', 'EXT-LOCK-001');
        $fixtures->assignUserToStructure($admin, $structure);

        $this->assertFalse($structure->isLocked(), 'Structure should start unlocked');

        $this->login($client, $admin);
        $csrf = $this->getCsrfToken($client->getContainer());

        $client->request('GET', sprintf(
            '/management/structures/toggle-lock-%d/%s',
            $structure->getId(),
            $csrf
        ));

        $em = $client->getContainer()->get('doctrine')->getManager();
        $em->clear();

        $structureRepo = $em->getRepository(Structure::class);
        $updated       = $structureRepo->find($structure->getId());
        $this->assertTrue($updated->isLocked(), 'Structure should be locked after toggle');
    }

    public function testToggleEnableStructure()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin     = $fixtures->createRawUser('struct_enable@example.com', 'password', true);
        $structure = $fixtures->createStructure('DISABLE ME STRUCTURE', 'EXT-ENABLE-001');
        $fixtures->assignUserToStructure($admin, $structure);

        $this->assertTrue($structure->isEnabled(), 'Structure should start enabled');

        $this->login($client, $admin);
        $csrf = $this->getCsrfToken($client->getContainer());

        $client->request('GET', sprintf(
            '/management/structures/toggle-enable-%d/%s',
            $structure->getId(),
            $csrf
        ));

        $em = $client->getContainer()->get('doctrine')->getManager();
        $em->clear();

        $structureRepo = $em->getRepository(Structure::class);
        $updated       = $structureRepo->find($structure->getId());
        $this->assertFalse($updated->isEnabled(), 'Structure should be disabled after toggle');
    }

    public function testNonAdminCannotCreate()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $user = $fixtures->createRawUser('struct_noadmin@example.com', 'password', false);
        $this->login($client, $user);

        $client->request('GET', '/management/structures/create');
        $this->assertTrue(
            $client->getResponse()->isForbidden() || $client->getResponse()->isRedirect(),
            'Non-admin user should not be able to access the create structure page'
        );
    }

    // ──────────────────────────────────────────────
    // GET /management/structures/pegass/{id}
    // ──────────────────────────────────────────────

    public function testPegassEndpointReturns404WhenNoPegassEntity(): void
    {
        $client    = static::createClient();
        $fixtures  = $this->getFixtures($client->getContainer());
        $admin     = $fixtures->createRawUser('struct_pegass-'.uniqid().'@test.com', 'password', true);
        $structure = $fixtures->createStructure('PEGSTR-'.uniqid(), 'EXT-PEG-'.uniqid());

        $this->login($client, $admin);
        $client->request('GET', sprintf('/management/structures/pegass/%d', $structure->getId()));

        // There's no Pegass entity attached to this fixture structure so the
        // controller raises NotFound.
        $this->assertResponseStatusCodeSame(404);
    }

    public function testPegassEndpointForbiddenForNonAdmin(): void
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());
        $user     = $fixtures->createRawUser('struct_pegass_user-'.uniqid().'@test.com', 'password', false);
        $structure = $fixtures->createStructure('PEGSTRU-'.uniqid(), 'EXT-PEGU-'.uniqid());

        $this->login($client, $user);
        $client->request('GET', sprintf('/management/structures/pegass/%d', $structure->getId()));

        $this->assertResponseStatusCodeSame(403);
    }

    // ──────────────────────────────────────────────
    // GET /management/structures/export/{id}
    // ──────────────────────────────────────────────

    public function testExportReturnsCsv(): void
    {
        $client    = static::createClient();
        $fixtures  = $this->getFixtures($client->getContainer());
        $user      = $fixtures->createRawUser('struct_exp-'.uniqid().'@test.com', 'password');
        $structure = $fixtures->createStructure('EXPSTR-'.uniqid(), 'EXT-EXP-'.uniqid());
        $fixtures->assignUserToStructure($user, $structure);

        $this->login($client, $user);
        $client->request('GET', sprintf('/management/structures/export/%d', $structure->getId()));

        $this->assertResponseIsSuccessful();
        // ArrayToCsvResponse sets the filename via Content-Disposition rather
        // than the content type (which is the generic application/octet-stream).
        $disposition = $client->getResponse()->headers->get('Content-Disposition');
        $this->assertNotNull($disposition);
        $this->assertStringContainsString('.csv', $disposition);
    }

    public function testExportForbiddenForOutsider(): void
    {
        $client    = static::createClient();
        $fixtures  = $this->getFixtures($client->getContainer());
        $user      = $fixtures->createRawUser('struct_exp_out-'.uniqid().'@test.com', 'password');
        $structure = $fixtures->createStructure('EXPSTRO-'.uniqid(), 'EXT-EXPO-'.uniqid());

        $this->login($client, $user);
        $client->request('GET', sprintf('/management/structures/export/%d', $structure->getId()));

        $this->assertResponseStatusCodeSame(403);
    }

    // ──────────────────────────────────────────────
    // POST /management/structures/list-users
    // ──────────────────────────────────────────────

    public function testListUsersReturnsJson(): void
    {
        $client    = static::createClient();
        $fixtures  = $this->getFixtures($client->getContainer());
        $user      = $fixtures->createRawUser('struct_lu-'.uniqid().'@test.com', 'password');
        $structure = $fixtures->createStructure('LUSTR-'.uniqid(), 'EXT-LU-'.uniqid());
        $fixtures->assignUserToStructure($user, $structure);

        $this->login($client, $user);
        $client->request('POST', '/management/structures/list-users', ['id' => $structure->getId()]);

        $this->assertResponseIsSuccessful();
        $decoded = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('title', $decoded);
        $this->assertArrayHasKey('body', $decoded);
    }

    public function testListUsersForbiddenForOutsider(): void
    {
        $client    = static::createClient();
        $fixtures  = $this->getFixtures($client->getContainer());
        $user      = $fixtures->createRawUser('struct_lu_out-'.uniqid().'@test.com', 'password');
        $structure = $fixtures->createStructure('LUSTRO-'.uniqid(), 'EXT-LUO-'.uniqid());

        $this->login($client, $user);
        $client->request('POST', '/management/structures/list-users', ['id' => $structure->getId()]);

        $this->assertResponseStatusCodeSame(403);
    }
}
