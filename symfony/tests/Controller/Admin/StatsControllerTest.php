<?php

namespace App\Tests\Controller\Admin;

use App\Tests\Base\BaseWebTestCase;
use App\Tests\Fixtures\DataFixtures;

class StatsControllerTest extends BaseWebTestCase
{
    private function getFixtures($container) : DataFixtures
    {
        return new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_encoder')
        );
    }

    public function testStatsHome()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin = $fixtures->createRawUser('stats_admin@test.com', 'password', true);

        $this->login($client, $admin);

        $crawler = $client->request('GET', '/admin/stats/');

        $this->assertResponseIsSuccessful();
    }

    public function testStatsGeneral()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin = $fixtures->createRawUser('stats_general_admin@test.com', 'password', true);

        $this->login($client, $admin);

        $crawler = $client->request('GET', '/admin/stats/general');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testStatsStructure()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin = $fixtures->createRawUser('stats_struct_admin@test.com', 'password', true);

        $this->login($client, $admin);

        $crawler = $client->request('GET', '/admin/stats/structure');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }
}
