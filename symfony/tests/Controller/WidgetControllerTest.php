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
            $container->get('security.password_hasher')
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

    // ──────────────────────────────────────────────
    // GET /widget/category-search
    // ──────────────────────────────────────────────

    public function testCategorySearchReturnsJsonArray(): void
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $user = $fixtures->createRawUser('widget_cat-'.uniqid().'@test.com', 'password');
        $this->login($client, $user);

        $client->request('GET', '/widget/category-search?keyword=foo');

        $this->assertResponseIsSuccessful();
        $this->assertIsArray(json_decode($client->getResponse()->getContent(), true));
    }

    // ──────────────────────────────────────────────
    // GET /widget/template-data
    // ──────────────────────────────────────────────

    public function testTemplateDataReturns404WhenNoId(): void
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $user = $fixtures->createRawUser('widget_tpl1-'.uniqid().'@test.com', 'password');
        $this->login($client, $user);

        $client->request('GET', '/widget/template-data');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testTemplateDataReturns404WhenIdUnknown(): void
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $user = $fixtures->createRawUser('widget_tpl2-'.uniqid().'@test.com', 'password');
        $this->login($client, $user);

        $client->request('GET', '/widget/template-data?id=99999999');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testTemplateDataReturns404WhenUserCannotAccessStructure(): void
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        // A template owned by a structure the user is not part of.
        $owner          = $fixtures->createRawUser('widget_tpl_owner-'.uniqid().'@test.com', 'password');
        $ownerStructure = $fixtures->createStructure('TPL-OWNER-STR-'.uniqid(), 'EXT-TPLOS-'.uniqid());
        $fixtures->assignUserToStructure($owner, $ownerStructure);
        $template       = $fixtures->createTemplate($ownerStructure, 'Some Template', 'sms', 'hi');

        $outsider = $fixtures->createRawUser('widget_tpl_outsider-'.uniqid().'@test.com', 'password');
        $this->login($client, $outsider);

        $client->request('GET', '/widget/template-data?id='.$template->getId());
        $this->assertResponseStatusCodeSame(404);
    }

    public function testTemplateDataReturnsJsonForAccessibleTemplate(): void
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $user      = $fixtures->createRawUser('widget_tpl_ok-'.uniqid().'@test.com', 'password');
        $structure = $fixtures->createStructure('TPL-OK-STR-'.uniqid(), 'EXT-TPLOK-'.uniqid());
        $fixtures->assignUserToStructure($user, $structure);
        $template  = $fixtures->createTemplate($structure, 'Acc Template', 'sms', 'Hello body');

        $this->login($client, $user);
        $client->request('GET', '/widget/template-data?id='.$template->getId());

        $this->assertResponseIsSuccessful();
        $decoded = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Hello body', $decoded['body']);
    }

    // ──────────────────────────────────────────────
    // searchAll=1 requires ROLE_ADMIN
    // ──────────────────────────────────────────────

    public function testVolunteerSearchAllForbiddenForNonAdmin(): void
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $user = $fixtures->createRawUser('widget_vol_all-'.uniqid().'@test.com', 'password', false);
        $this->login($client, $user);

        $client->request('GET', '/widget/volunteer-search/1?keyword=zzz');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testStructureSearchAllForbiddenForNonAdmin(): void
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $user = $fixtures->createRawUser('widget_str_all-'.uniqid().'@test.com', 'password', false);
        $this->login($client, $user);

        $client->request('GET', '/widget/structure-search/1?keyword=zzz');
        $this->assertResponseStatusCodeSame(403);
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
