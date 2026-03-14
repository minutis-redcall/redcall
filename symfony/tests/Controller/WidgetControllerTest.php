<?php

namespace App\Tests\Controller;

use App\Tests\Base\BaseWebTestCase;
use App\Tests\Fixtures\DataFixtures;

class WidgetControllerTest extends BaseWebTestCase
{
    private function getFixtures($container) : DataFixtures
    {
        return new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_encoder')
        );
    }

    public function testVolunteerSearch()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $user      = $fixtures->createRawUser('widget_vol@example.com', 'password');
        $volunteer = $fixtures->createVolunteer($user, 'WIDGET-VOL-001', 'widget_vol@example.com');
        $structure = $fixtures->createStructure('Widget Structure', 'WIDGET-STR-001');
        $fixtures->assignUserToStructure($user, $structure);
        $fixtures->assignVolunteerToStructure($volunteer, $structure);

        $this->login($client, $user);

        $client->request('GET', '/widget/volunteer-search/0?keyword=WIDGET');

        $this->assertResponseIsSuccessful();

        $response = $client->getResponse();
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
    }

    public function testStructureSearch()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $user      = $fixtures->createRawUser('widget_str@example.com', 'password');
        $structure = $fixtures->createStructure('Searchable Structure', 'WIDGET-STR-002');
        $fixtures->assignUserToStructure($user, $structure);

        $this->login($client, $user);

        $client->request('GET', '/widget/structure-search/0?keyword=Searchable');

        $this->assertResponseIsSuccessful();

        $response = $client->getResponse();
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
    }

    public function testBadgeSearch()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $user = $fixtures->createRawUser('widget_badge@example.com', 'password');
        $fixtures->createBadge('Searchable Badge', 'WIDGET-BADGE-001');

        $this->login($client, $user);

        $client->request('GET', '/widget/badge-search?keyword=Searchable');

        $this->assertResponseIsSuccessful();

        $response = $client->getResponse();
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
    }
}
