<?php

namespace App\Tests\Controller\Admin;

use App\Tests\Base\BaseWebTestCase;
use App\Tests\Fixtures\DataFixtures;

class MaintenanceControllerTest extends BaseWebTestCase
{
    private function getFixtures($container) : DataFixtures
    {
        return new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_encoder')
        );
    }

    public function testMaintenanceIndexAsRoot()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $root = $fixtures->createRawUser('root_maint@test.com', 'password', true);

        $this->login($client, $root);

        $crawler = $client->request('GET', '/admin/maintenance/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('a[href*="maintenance"]');
    }

    public function testMaintenanceIndexDeniedForAdmin()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        // Create an admin who is NOT root
        $admin = $fixtures->createRawUser('admin_maint@test.com', 'password', true);
        $admin->setIsRoot(false);
        $client->getContainer()->get('doctrine.orm.entity_manager')->flush();

        $this->login($client, $admin);

        $client->request('GET', '/admin/maintenance/');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testMaintenanceSearch()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $root = $fixtures->createRawUser('root_search@test.com', 'password', true);

        $this->login($client, $root);

        $crawler = $client->request('GET', '/admin/maintenance/search');

        $this->assertResponseIsSuccessful();
    }

    public function testMaintenanceMessage()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $root = $fixtures->createRawUser('root_msg@test.com', 'password', true);

        $this->login($client, $root);

        $crawler = $client->request('GET', '/admin/maintenance/message');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }
}
