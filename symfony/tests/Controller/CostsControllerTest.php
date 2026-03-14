<?php

namespace App\Tests\Controller;

use App\Tests\Base\BaseWebTestCase;
use App\Tests\Fixtures\DataFixtures;

class CostsControllerTest extends BaseWebTestCase
{
    private function getFixtures($container) : DataFixtures
    {
        return new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_encoder')
        );
    }

    public function testCostsHome()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $user = $fixtures->createRawUser('costs_user@example.com', 'password');

        $this->login($client, $user);

        $client->request('GET', '/costs/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.alert-info');
    }

    public function testCostsShowsStructureData()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $user      = $fixtures->createRawUser('costs_struct_user@example.com', 'password');
        $structure = $fixtures->createStructure('MY COST STRUCTURE', 'COST-EXT-001');
        $fixtures->assignUserToStructure($user, $structure);

        $this->login($client, $user);

        $crawler = $client->request('GET', '/costs/');

        $this->assertResponseIsSuccessful();
        // The page should render (even if no campaign data exists for the period)
        $this->assertSelectorExists('.alert-info');
    }

    public function testCostsRequiresAuth()
    {
        $client = static::createClient();

        $client->request('GET', '/costs/');

        // Anonymous users should be redirected to login
        $this->assertResponseRedirects();
    }
}
