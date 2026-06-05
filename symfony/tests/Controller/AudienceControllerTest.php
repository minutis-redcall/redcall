<?php

namespace App\Tests\Controller;

use App\Tests\Base\BaseWebTestCase;
use App\Tests\Fixtures\DataFixtures;

class AudienceControllerTest extends BaseWebTestCase
{
    private function getFixtures($container) : DataFixtures
    {
        return new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_hasher')
        );
    }

    private function setUpUser($client, array &$out = []): array
    {
        $container = $client->getContainer();
        $fixtures  = $this->getFixtures($container);

        $suffix    = uniqid();
        $user      = $fixtures->createRawUser("aud-{$suffix}@test.com", 'password');
        $structure = $fixtures->createStructure("AUD STRUCT {$suffix}", "EXT-AUD-{$suffix}");
        $fixtures->assignUserToStructure($user, $structure);
        $volunteer = $fixtures->createVolunteer($user, "VOL-AUD-{$suffix}", "aud-vol-{$suffix}@test.com");
        $fixtures->assignVolunteerToStructure($volunteer, $structure);

        $this->login($client, $user);

        return compact('user', 'structure', 'volunteer');
    }

    /**
     * Build the audience-form payload that numbers/problems/selection consume.
     * The endpoints walk the request via the `name` query argument
     * (AudienceType::getAudienceFormData).
     */
    private function audiencePayload(array $audienceOverrides = []): array
    {
        $audience = array_merge([
            'preselection_key'    => '',
            'volunteers'          => '',
            'excluded_volunteers' => '',
            'external_ids'        => '',
            'allow_minors'        => '0',
            'structures_global'   => '',
            'structures_local'    => '',
            'badges_all'          => '1',
            'badges_ticked'       => '',
            'badges_searched'     => '',
            'test_on_me'          => '0',
        ], $audienceOverrides);

        return ['campaign' => ['trigger' => ['audience' => $audience]]];
    }

    // ──────────────────────────────────────────────
    // GET /audience/search-volunteer
    // ──────────────────────────────────────────────

    public function testSearchVolunteerReturnsJsonArray(): void
    {
        $client = static::createClient();
        $this->setUpUser($client);

        $client->request('GET', '/audience/search-volunteer?keyword=zzz');

        $this->assertResponseIsSuccessful();
        $decoded = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($decoded);
    }

    public function testSearchVolunteerRedirectsAnonymous(): void
    {
        $client = static::createClient();
        $client->request('GET', '/audience/search-volunteer?keyword=foo');
        $this->assertResponseStatusCodeSame(302);
    }

    // ──────────────────────────────────────────────
    // GET /audience/search-badge
    // ──────────────────────────────────────────────

    public function testSearchBadgeReturnsJsonArray(): void
    {
        $client = static::createClient();
        $this->setUpUser($client);

        $client->request('GET', '/audience/search-badge?keyword=zzz');

        $this->assertResponseIsSuccessful();
        $decoded = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($decoded);
    }

    // ──────────────────────────────────────────────
    // GET /audience/numbers
    // ──────────────────────────────────────────────

    public function testNumbersReturnsJsonClassification(): void
    {
        $client = static::createClient();
        $this->setUpUser($client);

        $client->request(
            'POST',
            '/audience/numbers?name=campaign[trigger][audience]',
            $this->audiencePayload()
        );

        $this->assertResponseIsSuccessful();
        $decoded = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('classification', $decoded);
        $this->assertArrayHasKey('triggered_count', $decoded);
    }

    // ──────────────────────────────────────────────
    // GET /audience/problems
    // ──────────────────────────────────────────────

    public function testProblemsRendersHtml(): void
    {
        $client = static::createClient();
        $this->setUpUser($client);

        $client->request(
            'POST',
            '/audience/problems?name=campaign[trigger][audience]',
            $this->audiencePayload()
        );

        $this->assertResponseIsSuccessful();
        // Renders the problems partial — header is its body class.
        $this->assertNotEmpty($client->getResponse()->getContent());
    }

    // ──────────────────────────────────────────────
    // GET /audience/selection
    // ──────────────────────────────────────────────

    public function testSelectionRendersHtml(): void
    {
        $client = static::createClient();
        $this->setUpUser($client);

        $client->request(
            'POST',
            '/audience/selection?name=campaign[trigger][audience]',
            $this->audiencePayload()
        );

        $this->assertResponseIsSuccessful();
        $this->assertNotEmpty($client->getResponse()->getContent());
    }

    // ──────────────────────────────────────────────
    // GET /audience/home
    // ──────────────────────────────────────────────

    public function testHomeRendersForTrustedUser(): void
    {
        $client = static::createClient();
        $this->setUpUser($client);

        $client->request('GET', '/audience/home');

        $this->assertResponseIsSuccessful();
        $this->assertNotEmpty($client->getResponse()->getContent());
    }

    // ──────────────────────────────────────────────
    // GET /audience/resolve
    // ──────────────────────────────────────────────

    public function testResolveRenders(): void
    {
        $client = static::createClient();
        $this->setUpUser($client);

        $client->request('GET', '/audience/resolve');

        $this->assertResponseIsSuccessful();
        $this->assertNotEmpty($client->getResponse()->getContent());
    }

    public function testResolveRedirectsAnonymous(): void
    {
        $client = static::createClient();
        $client->request('GET', '/audience/resolve');
        $this->assertResponseStatusCodeSame(302);
    }
}
