<?php

namespace App\Tests\Controller\Admin;

use App\Tests\Base\BaseWebTestCase;
use App\Tests\Fixtures\DataFixtures;

class CampaignAdminControllerTest extends BaseWebTestCase
{
    private function getFixtures($container) : DataFixtures
    {
        return new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_encoder')
        );
    }

    public function testAdminCampaignList()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin = $fixtures->createRawUser('campaign_admin@test.com', 'password', true);

        $this->login($client, $admin);

        $crawler = $client->request('GET', '/admin/campaign');

        $this->assertResponseIsSuccessful();
    }
}
