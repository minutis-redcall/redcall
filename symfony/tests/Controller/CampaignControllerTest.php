<?php

namespace App\Tests\Controller;

use App\Entity\Campaign;
use App\Entity\Report;
use App\Entity\ReportRepartition;
use App\Entity\User;
use App\Tests\Base\BaseWebTestCase;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CampaignControllerTest extends BaseWebTestCase
{
    private function getFixtures($container) : DataFixtures
    {
        return new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_hasher')
        );
    }

    private function getCsrfToken($container, string $tokenId = 'campaign') : string
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

    /**
     * Creates a user with a volunteer, a structure, and a fully linked campaign
     * so that the CAMPAIGN_ACCESS and CAMPAIGN_OWNER voters grant access.
     *
     * @return array{user: User, campaign: Campaign}
     */
    private function createAccessibleCampaign($container, string $label = 'My Campaign', bool $active = true) : array
    {
        $fixtures = $this->getFixtures($container);

        $user      = $fixtures->createRawUser('campaign_user@example.com', 'password');
        $structure = $fixtures->createStructure('STRUCT A', 'EXT-CAMP-001');
        $fixtures->assignUserToStructure($user, $structure);
        $volunteer = $fixtures->createVolunteer($user, 'VOL-CAMP-001', 'vol@camp.test');
        $fixtures->assignVolunteerToStructure($volunteer, $structure);

        $campaign = $fixtures->createCampaign($label, Campaign::TYPE_GREEN, $active);
        $campaign->setVolunteer($volunteer);
        $fixtures->getEntityManager()->flush();

        $communication = $fixtures->createCommunication($campaign);
        $fixtures->createMessage($communication, $volunteer);

        return [
            'user'     => $user,
            'campaign' => $campaign,
        ];
    }

    public function testListCampaignsEmpty()
    {
        $client    = static::createClient();
        $container = $client->getContainer();
        $fixtures  = $this->getFixtures($container);

        $user = $fixtures->createRawUser('empty_list@example.com', 'password');
        $this->login($client, $user);

        $client->request('GET', '/campaign/list');

        $this->assertResponseStatusCodeSame(200);
    }

    public function testListCampaignsShowsCampaigns()
    {
        $client    = static::createClient();
        $container = $client->getContainer();

        $data = $this->createAccessibleCampaign($container, 'Visible Campaign Label');
        $this->login($client, $data['user']);

        $crawler = $client->request('GET', '/campaign/list');

        $this->assertResponseStatusCodeSame(200);
        $this->assertStringContainsString(
            'Visible Campaign Label',
            $crawler->text()
        );
    }

    public function testCloseCampaign()
    {
        $client = static::createClient();
        $client->followRedirects();
        $container = $client->getContainer();

        $data     = $this->createAccessibleCampaign($container, 'Campaign To Close');
        $campaign = $data['campaign'];
        $this->login($client, $data['user']);

        $csrf = $this->getCsrfToken($container);

        $client->request('GET', sprintf('/campaign/%d/close/%s', $campaign->getId(), $csrf));

        $this->assertResponseIsSuccessful();

        // Verify in database
        $em = $container->get('doctrine.orm.entity_manager');
        $em->clear();
        $refreshed = $em->find(Campaign::class, $campaign->getId());
        $this->assertFalse((bool) $refreshed->isActive(), 'Campaign should be inactive after closing');
    }

    public function testOpenCampaign()
    {
        $client = static::createClient();
        $client->followRedirects();
        $container = $client->getContainer();

        // Create an already-closed campaign
        $data     = $this->createAccessibleCampaign($container, 'Campaign To Reopen', false);
        $campaign = $data['campaign'];
        $this->login($client, $data['user']);

        $csrf = $this->getCsrfToken($container);

        $client->request('GET', sprintf('/campaign/%d/open/%s', $campaign->getId(), $csrf));

        $this->assertResponseIsSuccessful();

        // Verify in database
        $em = $container->get('doctrine.orm.entity_manager');
        $em->clear();
        $refreshed = $em->find(Campaign::class, $campaign->getId());
        $this->assertTrue((bool) $refreshed->isActive(), 'Campaign should be active after reopening');
    }

    public function testRenameCampaign()
    {
        $client = static::createClient();
        $client->followRedirects();
        $container = $client->getContainer();

        $data     = $this->createAccessibleCampaign($container, 'Old Name');
        $campaign = $data['campaign'];
        $this->login($client, $data['user']);

        $csrf = $this->getCsrfToken($container);

        $client->request('POST', sprintf('/campaign/%d/rename', $campaign->getId()), [
            'csrf'     => $csrf,
            'new_name' => 'Renamed Campaign',
        ]);

        $this->assertResponseIsSuccessful();

        // Verify in database
        $em = $container->get('doctrine.orm.entity_manager');
        $em->clear();
        $refreshed = $em->find(Campaign::class, $campaign->getId());
        $this->assertSame('Renamed Campaign', $refreshed->getLabel());
    }

    public function testNotesCampaign()
    {
        $client = static::createClient();
        $client->followRedirects();
        $container = $client->getContainer();

        $data     = $this->createAccessibleCampaign($container, 'Notes Campaign');
        $campaign = $data['campaign'];
        $this->login($client, $data['user']);

        $csrf = $this->getCsrfToken($container);

        $client->request('POST', sprintf('/campaign/%d/notes', $campaign->getId()), [
            'csrf'  => $csrf,
            'notes' => 'These are important notes about the campaign.',
        ]);

        $this->assertResponseIsSuccessful();

        // Verify in database
        $em = $container->get('doctrine.orm.entity_manager');
        $em->clear();
        $refreshed = $em->find(Campaign::class, $campaign->getId());
        $this->assertSame('These are important notes about the campaign.', $refreshed->getNotes());
    }

    public function testColorCampaign()
    {
        $client = static::createClient();
        $client->followRedirects();
        $container = $client->getContainer();

        $data     = $this->createAccessibleCampaign($container, 'Color Campaign');
        $campaign = $data['campaign'];
        $this->login($client, $data['user']);

        $this->assertSame(Campaign::TYPE_GREEN, $campaign->getType());

        $csrf = $this->getCsrfToken($container);

        $client->request('GET', sprintf(
            '/campaign/%d/change-color/%s/%s',
            $campaign->getId(),
            Campaign::TYPE_RED,
            $csrf
        ));

        $this->assertResponseIsSuccessful();

        // Verify in database
        $em = $container->get('doctrine.orm.entity_manager');
        $em->clear();
        $refreshed = $em->find(Campaign::class, $campaign->getId());
        $this->assertSame(Campaign::TYPE_RED, $refreshed->getType());
    }

    public function testCampaignReport()
    {
        $client    = static::createClient();
        $container = $client->getContainer();

        $data = $this->createAccessibleCampaign($container, 'Report Campaign');

        // The report template accesses communication.report.repartitions, so we
        // need to create a Report entity linked to the communication.
        $em            = $container->get('doctrine.orm.entity_manager');
        $communication = $data['campaign']->getCommunications()->first();

        $report = new Report();
        $report->setType($communication->getType());
        $report->setCosts([]);
        $communication->setReport($report);

        $repartition = new ReportRepartition();
        $repartition->setStructure($data['user']->getStructures()->first());
        $repartition->setRatio(100.0);
        $report->addRepartition($repartition);

        $em->persist($report);
        $em->persist($communication);
        $em->flush();

        $this->login($client, $data['user']);

        $crawler = $client->request('GET', sprintf('/campaign/%d/report', $data['campaign']->getId()));

        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorExists('table.table-striped');
    }

    // ──────────────────────────────────────────────
    // GET /campaign/{id}/audience -> JSON audience
    // ──────────────────────────────────────────────

    public function testAudienceReturnsJson()
    {
        $client    = static::createClient();
        $container = $client->getContainer();
        $data      = $this->createAccessibleCampaign($container, 'Audience JSON Campaign');

        $this->login($client, $data['user']);
        $client->request('GET', sprintf('/campaign/%d/audience', $data['campaign']->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());
    }

    public function testAudienceReturns404ForUnknownId()
    {
        $client    = static::createClient();
        $container = $client->getContainer();
        $data      = $this->createAccessibleCampaign($container, 'AudienceCampaign-404');

        $this->login($client, $data['user']);
        $client->request('GET', '/campaign/9999999/audience');

        $this->assertResponseStatusCodeSame(404);
    }

    // ──────────────────────────────────────────────
    // GET /campaign/{id}/keep/{csrf} -> JSON postpone
    // ──────────────────────────────────────────────

    public function testKeepCampaignPostponesExpiration()
    {
        $client    = static::createClient();
        $container = $client->getContainer();
        $data      = $this->createAccessibleCampaign($container, 'Keep Campaign');

        $this->login($client, $data['user']);
        $csrf = $this->getCsrfToken($container);

        $client->request('GET', sprintf('/campaign/%d/keep/%s', $data['campaign']->getId(), $csrf));

        $this->assertResponseIsSuccessful();
        $decoded = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('expiresAt', $decoded);
    }

    public function testKeepCampaignWithBadCsrfReturns404()
    {
        $client    = static::createClient();
        $container = $client->getContainer();
        $data      = $this->createAccessibleCampaign($container, 'Keep BadCsrf');

        $this->login($client, $data['user']);
        $client->request('GET', sprintf('/campaign/%d/keep/%s', $data['campaign']->getId(), 'invalid-csrf'));

        $this->assertResponseStatusCodeSame(404);
    }

    // ──────────────────────────────────────────────
    // GET /campaign/operations -> JSON
    // ──────────────────────────────────────────────

    public function testSearchForOperationReturns404WhenStructureUnknown()
    {
        $client    = static::createClient();
        $container = $client->getContainer();
        $data      = $this->createAccessibleCampaign($container, 'SearchOp Campaign');

        $this->login($client, $data['user']);
        $client->request('GET', '/campaign/operations?externalId=DOES-NOT-EXIST');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testSearchForOperationReturnsJsonForAccessibleStructure()
    {
        $client    = static::createClient();
        $container = $client->getContainer();
        $fixtures  = $this->getFixtures($container);

        // Use a numeric externalId — FakeOperationRepository::search() and the
        // underlying FakeOperation.structureExternalId column are both typed
        // as int, even though Structure.externalId in the main schema is a
        // string. Cleaner than reshaping the sandbox provider for this test.
        $user      = $fixtures->createRawUser('campaign_user_so@example.com', 'password');
        $structure = $fixtures->createStructure('STRUCT SO', '12345678');
        $fixtures->assignUserToStructure($user, $structure);
        $volunteer = $fixtures->createVolunteer($user, 'VOL-SO-001', 'vol@so.test');
        $fixtures->assignVolunteerToStructure($volunteer, $structure);

        $this->login($client, $user);
        $client->request('GET', '/campaign/operations?externalId='.$structure->getExternalId());

        $this->assertResponseIsSuccessful();
        $decoded = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('operations', $decoded);
    }

    // ──────────────────────────────────────────────
    // Auth/404 cross-checks
    // ──────────────────────────────────────────────

    public function testCampaignReportReturns404ForUnknownId()
    {
        $client    = static::createClient();
        $container = $client->getContainer();
        $data      = $this->createAccessibleCampaign($container, 'Report404');

        $this->login($client, $data['user']);
        $client->request('GET', '/campaign/99999999/report');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testListCampaignsRedirectsAnonymous()
    {
        $client = static::createClient();
        $client->request('GET', '/campaign/list');

        $this->assertResponseStatusCodeSame(302);
    }

    public function testAccessDeniedForNonOwner()
    {
        $client    = static::createClient();
        $container = $client->getContainer();

        // Create a campaign accessible to one user
        $data = $this->createAccessibleCampaign($container, 'Restricted Campaign');

        // Create a second user with NO shared structures
        $fixtures       = $this->getFixtures($container);
        $otherUser      = $fixtures->createRawUser('outsider@example.com', 'password');
        $otherStructure = $fixtures->createStructure('OTHER STRUCT', 'EXT-OTHER-001');
        $fixtures->assignUserToStructure($otherUser, $otherStructure);

        $this->login($client, $otherUser);

        // Attempt to access the campaign report (requires CAMPAIGN_ACCESS)
        $client->request('GET', sprintf('/campaign/%d/report', $data['campaign']->getId()));

        $this->assertResponseStatusCodeSame(403);

        // Attempt to close the campaign (requires CAMPAIGN_OWNER)
        $csrf = $this->getCsrfToken($container);
        $client->request('GET', sprintf('/campaign/%d/close/%s', $data['campaign']->getId(), $csrf));

        $this->assertTrue(
            $client->getResponse()->isForbidden(),
            'Non-owner should not be able to close a campaign they do not have access to'
        );
    }
}
