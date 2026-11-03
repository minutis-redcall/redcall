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
    // Perimeter leaks on problems/selection (CERT-FR RM #1080870, audit H2)
    //
    // Both endpoints accept arbitrary volunteer ids / NIVOLs and used to render
    // the full identity card (NIVOL, email, phone…) of every volunteer in the
    // classification, including those outside the user's structures.
    // ──────────────────────────────────────────────

    /**
     * A volunteer in a structure the logged-in user does not manage.
     */
    private function createForeignVolunteer($client, bool $enabled = true): array
    {
        $container = $client->getContainer();
        $fixtures  = $this->getFixtures($container);

        $suffix    = uniqid();
        $structure = $fixtures->createStructure("FOREIGN STRUCT {$suffix}", "EXT-FOREIGN-{$suffix}");
        $email     = "foreign-{$suffix}@leak.test";
        $volunteer = $fixtures->createStandaloneVolunteer("FOREIGN-{$suffix}", $email);
        $fixtures->assignVolunteerToStructure($volunteer, $structure);

        if (!$enabled) {
            $em = $container->get('doctrine.orm.entity_manager');
            $volunteer->setEnabled(false);
            $em->persist($volunteer);
            $em->flush();
        }

        return ['volunteer' => $volunteer, 'email' => $email, 'externalId' => $volunteer->getExternalId()];
    }

    private function postAudience($client, string $endpoint, array $audience): string
    {
        $client->request(
            'POST',
            sprintf('/audience/%s?name=campaign[trigger][audience]', $endpoint),
            $this->audiencePayload($audience)
        );

        $this->assertResponseIsSuccessful();

        return $client->getResponse()->getContent();
    }

    public function testProblemsDoesNotExposeForeignVolunteerById(): void
    {
        $client  = static::createClient();
        $this->setUpUser($client);
        $foreign = $this->createForeignVolunteer($client);

        $html = $this->postAudience($client, 'problems', [
            'volunteers' => (string) $foreign['volunteer']->getId(),
        ]);

        $this->assertStringNotContainsString($foreign['email'], $html);
        $this->assertStringNotContainsString($foreign['externalId'], $html);
    }

    public function testProblemsDoesNotExposeForeignVolunteerByExternalId(): void
    {
        $client  = static::createClient();
        $this->setUpUser($client);
        $foreign = $this->createForeignVolunteer($client);

        $html = $this->postAudience($client, 'problems', [
            'external_ids' => $foreign['externalId'],
        ]);

        $this->assertStringNotContainsString($foreign['email'], $html);
    }

    public function testProblemsDoesNotExposeDisabledForeignVolunteer(): void
    {
        $client  = static::createClient();
        $this->setUpUser($client);
        $foreign = $this->createForeignVolunteer($client, false);

        $html = $this->postAudience($client, 'problems', [
            'volunteers' => (string) $foreign['volunteer']->getId(),
        ]);

        $this->assertStringNotContainsString($foreign['email'], $html);
    }

    public function testSelectionDoesNotExposeForeignExcludedVolunteer(): void
    {
        $client  = static::createClient();
        $this->setUpUser($client);
        $foreign = $this->createForeignVolunteer($client);

        $html = $this->postAudience($client, 'selection', [
            'excluded_volunteers' => (string) $foreign['volunteer']->getId(),
        ]);

        $this->assertStringNotContainsString($foreign['email'], $html);
    }

    public function testSelectionDoesNotExposeForeignVolunteerById(): void
    {
        $client  = static::createClient();
        $this->setUpUser($client);
        $foreign = $this->createForeignVolunteer($client);

        $html = $this->postAudience($client, 'selection', [
            'volunteers' => (string) $foreign['volunteer']->getId(),
        ]);

        $this->assertStringNotContainsString($foreign['email'], $html);
    }

    // Guards: the fix must not hide volunteers the user is allowed to see.

    public function testSelectionStillShowsOwnVolunteer(): void
    {
        $client = static::createClient();
        $data   = $this->setUpUser($client);

        $html = $this->postAudience($client, 'selection', [
            'volunteers' => (string) $data['volunteer']->getId(),
        ]);

        $this->assertStringContainsString($data['volunteer']->getEmail(), $html);
    }

    public function testProblemsStillShowsOwnDisabledVolunteer(): void
    {
        $client    = static::createClient();
        $data      = $this->setUpUser($client);
        $container = $client->getContainer();
        $em        = $container->get('doctrine.orm.entity_manager');

        $suffix = uniqid();
        $own    = $this->getFixtures($container)->createStandaloneVolunteer("OWN-DIS-{$suffix}", "own-dis-{$suffix}@test.com");
        $this->getFixtures($container)->assignVolunteerToStructure($own, $data['structure']);
        $own->setEnabled(false);
        $em->persist($own);
        $em->flush();

        $html = $this->postAudience($client, 'problems', [
            'volunteers' => (string) $own->getId(),
        ]);

        $this->assertStringContainsString("own-dis-{$suffix}@test.com", $html);
    }

    public function testProblemsShowsForeignVolunteerToAdmin(): void
    {
        $client    = static::createClient();
        $container = $client->getContainer();
        $fixtures  = $this->getFixtures($container);
        $suffix    = uniqid();
        $admin     = $fixtures->createRawUser("aud-admin-{$suffix}@test.com", 'password', true);
        $this->login($client, $admin);
        $foreign   = $this->createForeignVolunteer($client, false);

        $html = $this->postAudience($client, 'problems', [
            'volunteers' => (string) $foreign['volunteer']->getId(),
        ]);

        $this->assertStringContainsString($foreign['email'], $html);
    }

    // ──────────────────────────────────────────────
    // Structure scoping on numbers (CERT-FR RM #1080870, audit M4)
    //
    // Structure ids were taken from the request as-is, so any user could
    // count volunteers (per badge) in any structure of the organisation.
    // ──────────────────────────────────────────────

    private function createStructureWithVolunteers($client, int $count, $parent = null)
    {
        $container = $client->getContainer();
        $fixtures  = $this->getFixtures($container);
        $suffix    = uniqid();
        $structure = $fixtures->createStructure("M4 {$suffix}", "EXT-M4-{$suffix}");

        if ($parent) {
            $em = $container->get('doctrine.orm.entity_manager');
            $structure->setParentStructure($parent);
            $em->persist($structure);
            $em->flush();
        }

        for ($i = 0; $i < $count; $i++) {
            $volunteer = $fixtures->createStandaloneVolunteer("M4-{$suffix}-{$i}", "m4-{$suffix}-{$i}@test.com");
            $fixtures->assignVolunteerToStructure($volunteer, $structure);
        }

        return $structure;
    }

    private function postNumbers($client, array $structureIds): array
    {
        $client->request(
            'POST',
            '/audience/numbers?name=campaign[trigger][audience]',
            $this->audiencePayload(['structures_global' => implode(',', $structureIds)])
        );
        $this->assertResponseIsSuccessful();

        return json_decode($client->getResponse()->getContent(), true);
    }

    public function testNumbersIgnoresStructuresOutsidePerimeter(): void
    {
        $client  = static::createClient();
        $this->setUpUser($client);
        $foreign = $this->createStructureWithVolunteers($client, 3);

        $numbers = $this->postNumbers($client, [$foreign->getId()]);

        $this->assertSame(0, $numbers['badge_counts'][0]);
        $this->assertSame(0, $numbers['triggered_count']);
    }

    public function testNumbersStillCountsOwnAndChildStructures(): void
    {
        $client = static::createClient();
        $data   = $this->setUpUser($client); // 1 volunteer in the user's structure
        $child  = $this->createStructureWithVolunteers($client, 2, $data['structure']);

        $numbers = $this->postNumbers($client, [$data['structure']->getId(), $child->getId()]);

        $this->assertSame(3, $numbers['badge_counts'][0]);
        $this->assertSame(3, $numbers['triggered_count']);
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
