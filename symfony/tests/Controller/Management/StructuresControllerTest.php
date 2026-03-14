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
            $container->get('security.password_encoder')
        );
    }

    private function getCsrfToken($container, string $tokenId = 'token') : string
    {
        /** @var CsrfTokenManagerInterface $tokenManager */
        $tokenManager = $container->get('security.csrf.token_manager');

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
}
