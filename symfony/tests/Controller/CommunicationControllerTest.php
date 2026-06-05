<?php

namespace App\Tests\Controller;

use App\Entity\Communication;
use App\Entity\User;
use App\Tests\Base\BaseWebTestCase;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CommunicationControllerTest extends BaseWebTestCase
{
    private function getFixtures($container) : DataFixtures
    {
        return new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_hasher')
        );
    }

    private function createCampaignForUser(DataFixtures $fixtures, User $user) : array
    {
        $structure = $fixtures->createStructure('COMM STRUCT', 'EXT-COMM-001');
        $fixtures->assignUserToStructure($user, $structure);
        $volunteer = $fixtures->createVolunteer($user, 'VOL-COMM-001', 'vol@comm.test');
        $fixtures->assignVolunteerToStructure($volunteer, $structure);
        $campaign      = $fixtures->createCampaign('Comm Test Campaign');
        $communication = $fixtures->createCommunication($campaign);
        $message       = $fixtures->createMessage($communication, $volunteer);

        return [$campaign, $communication, $message, $structure, $volunteer];
    }

    public function testCommunicationIndex()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $user = $fixtures->createRawUser('comm_index@example.com', 'password');
        [$campaign, $communication, $message, $structure, $volunteer] = $this->createCampaignForUser($fixtures, $user);

        $this->login($client, $user);

        $crawler = $client->request('GET', sprintf('/campaign/%d', $campaign->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Comm Test Campaign');
    }

    public function testShortPolling()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $user = $fixtures->createRawUser('comm_short@example.com', 'password');
        [$campaign] = $this->createCampaignForUser($fixtures, $user);

        $this->login($client, $user);

        $client->request('GET', sprintf('/campaign/%d/short-polling', $campaign->getId()));

        $this->assertResponseIsSuccessful();
        $response = $client->getResponse();
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
    }

    public function testLongPolling()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $user = $fixtures->createRawUser('comm_long@example.com', 'password');
        [$campaign] = $this->createCampaignForUser($fixtures, $user);

        $this->login($client, $user);

        // Send a hash that will not match, so the endpoint returns immediately with JSON
        $client->request('GET', sprintf('/campaign/%d/long-polling?hash=nonexistent', $campaign->getId()));

        $this->assertResponseIsSuccessful();
        $response = $client->getResponse();
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('hash', $data);
    }

    public function testProviderInformation()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $user = $fixtures->createRawUser('comm_provider@example.com', 'password');
        [$campaign, $communication, $message] = $this->createCampaignForUser($fixtures, $user);

        // Set a fake messageId so getBySid() doesn't receive null
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $message->setMessageId('SM_fake_sid_for_test');
        $em->persist($message);
        $em->flush();

        $this->login($client, $user);

        $client->request('GET', sprintf(
            '/campaign/%d/provider-information/%d',
            $campaign->getId(),
            $message->getId()
        ));

        $this->assertResponseIsSuccessful();
        $response = $client->getResponse();
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('body', $data);
    }

    public function testCommunicationRename()
    {
        $client = static::createClient();
        $client->followRedirects();
        $fixtures = $this->getFixtures($client->getContainer());

        $user = $fixtures->createRawUser('comm_rename@example.com', 'password');
        [$campaign, $communication, $message] = $this->createCampaignForUser($fixtures, $user);

        $this->login($client, $user);

        /** @var CsrfTokenManagerInterface $tokenManager */
        $tokenManager = $client->getContainer()->get('security.csrf.token_manager');
        $csrf         = $tokenManager->getToken('communication')->getValue();

        $client->request('POST', sprintf(
            '/campaign/%d/rename-communication/%d',
            $campaign->getId(),
            $communication->getId()
        ), [
            'new_name' => 'Renamed Communication',
            'csrf'     => $csrf,
        ]);

        $this->assertResponseIsSuccessful();

        // Verify the name was changed in the database
        $em = $client->getContainer()->get('doctrine')->getManager();
        $em->clear();
        $updatedCommunication = $em->getRepository(Communication::class)->find($communication->getId());
        $this->assertSame('Renamed Communication', $updatedCommunication->getLabel());
    }

    // ──────────────────────────────────────────────
    // GET /campaign/goto/{id} -> redirect to campaign
    // ──────────────────────────────────────────────

    public function testGotoCommunicationRedirectsToCampaign(): void
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());
        $user     = $fixtures->createRawUser('comm_goto-'.uniqid().'@example.com', 'password');
        [$campaign, $communication] = $this->createCampaignForUser($fixtures, $user);

        $this->login($client, $user);
        $client->request('GET', sprintf('/campaign/goto/%d', $communication->getId()));

        $this->assertResponseRedirects(sprintf('/campaign/%d', $campaign->getId()));
    }

    public function testGotoCommunicationDeniesOutsider(): void
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $owner = $fixtures->createRawUser('comm_goto_owner-'.uniqid().'@example.com', 'password');
        [, $communication] = $this->createCampaignForUser($fixtures, $owner);

        $outsider = $fixtures->createRawUser('comm_goto_outsider-'.uniqid().'@example.com', 'password');
        $fixtures->assignUserToStructure(
            $outsider,
            $fixtures->createStructure('OUTSIDER-GOTO', 'EXT-OUTGOTO-'.uniqid())
        );
        $this->login($client, $outsider);

        $client->request('GET', sprintf('/campaign/goto/%d', $communication->getId()));

        $this->assertResponseStatusCodeSame(403);
    }

    // ──────────────────────────────────────────────
    // POST /campaign/{id}/add-communication/{type}
    // ──────────────────────────────────────────────

    public function testAddCommunicationRedirectsToNewWithKey(): void
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());
        $user     = $fixtures->createRawUser('comm_add-'.uniqid().'@example.com', 'password');
        [$campaign, , , , $volunteer] = $this->createCampaignForUser($fixtures, $user);

        $this->login($client, $user);
        $client->request('POST', sprintf('/campaign/%d/add-communication/sms', $campaign->getId()), [
            'volunteers' => json_encode([$volunteer->getId()]),
        ]);

        $this->assertResponseStatusCodeSame(302);
        $location = $client->getResponse()->headers->get('Location');
        $this->assertStringContainsString('/new-communication/sms/', (string) $location);
    }

    public function testAddCommunicationRedirectsUserWithoutStructuresToHome(): void
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());
        $owner    = $fixtures->createRawUser('comm_add_owner-'.uniqid().'@example.com', 'password');
        [$campaign] = $this->createCampaignForUser($fixtures, $owner);

        // The outsider has no structures/volunteer, so the controller redirects to home.
        // But CAMPAIGN_ACCESS voter denies before that — we test the auth gate here.
        $outsider = $fixtures->createRawUser('comm_add_outsider-'.uniqid().'@example.com', 'password');
        $this->login($client, $outsider);
        $client->request('POST', sprintf('/campaign/%d/add-communication/sms', $campaign->getId()));

        $this->assertResponseStatusCodeSame(403);
    }

    // ──────────────────────────────────────────────
    // GET /campaign/{id}/new-communication/{type}/{key}
    // ──────────────────────────────────────────────

    public function testNewCommunicationFormRenders(): void
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());
        $user     = $fixtures->createRawUser('comm_new-'.uniqid().'@example.com', 'password');
        [$campaign] = $this->createCampaignForUser($fixtures, $user);

        $this->login($client, $user);
        $client->request('GET', sprintf('/campaign/%d/new-communication/sms', $campaign->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    // ──────────────────────────────────────────────
    // POST /campaign/preview/{type}
    // ──────────────────────────────────────────────

    public function testPreviewReturnsFalseWhenMessageEmpty(): void
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());
        $user     = $fixtures->createRawUser('comm_preview-'.uniqid().'@example.com', 'password');
        $this->createCampaignForUser($fixtures, $user);

        $this->login($client, $user);
        // GET so the underlying form's handleRequest doesn't try to bind an
        // incomplete trigger body — the controller short-circuits on empty
        // message and returns {success: false}.
        $client->request('GET', '/campaign/preview/sms');

        $this->assertResponseIsSuccessful();
        $decoded = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('success', $decoded);
        $this->assertFalse($decoded['success']);
    }

    // ──────────────────────────────────────────────
    // POST /campaign/play -> JSON
    // ──────────────────────────────────────────────

    public function testPlayReturnsFalseWhenMessageEmpty(): void
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());
        $user     = $fixtures->createRawUser('comm_play-'.uniqid().'@example.com', 'password');
        $this->createCampaignForUser($fixtures, $user);

        $this->login($client, $user);
        // GET so the form's handleRequest doesn't attempt to bind the (empty)
        // body — the trigger keeps its default null message and the controller
        // short-circuits with {success: false}.
        $client->request('GET', '/campaign/play');

        $this->assertResponseIsSuccessful();
        $decoded = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($decoded);
        $this->assertFalse($decoded['success']);
    }

    // ──────────────────────────────────────────────
    // GET /campaign/answers -> requires messageId
    // ──────────────────────────────────────────────

    public function testAnswersReturns404WhenNoMessageId(): void
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());
        $user     = $fixtures->createRawUser('comm_ans-'.uniqid().'@example.com', 'password');
        $this->createCampaignForUser($fixtures, $user);

        $this->login($client, $user);
        $client->request('GET', '/campaign/answers');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testAnswersReturns404WhenMessageUnknown(): void
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());
        $user     = $fixtures->createRawUser('comm_ans2-'.uniqid().'@example.com', 'password');
        $this->createCampaignForUser($fixtures, $user);

        $this->login($client, $user);
        $client->request('GET', '/campaign/answers?messageId=99999999');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testAnswersRendersForOwnedMessage(): void
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());
        $user     = $fixtures->createRawUser('comm_ans3-'.uniqid().'@example.com', 'password');
        [, , $message] = $this->createCampaignForUser($fixtures, $user);

        $this->login($client, $user);
        $client->request('GET', '/campaign/answers?messageId='.$message->getId());

        $this->assertResponseIsSuccessful();
        // The template renders the volunteer's display name as the heading;
        // the reply form is conditional on the message being phone-reachable,
        // which test fixtures don't set up. Assert on the stable header.
        $this->assertSelectorExists('h4.text-center');
    }

    // ──────────────────────────────────────────────
    // POST /campaign/answer/{csrf}/{id}
    // ──────────────────────────────────────────────

    public function testChangeAnswerWithBadCsrfReturns404(): void
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());
        $user     = $fixtures->createRawUser('comm_chans-'.uniqid().'@example.com', 'password');
        [, , $message] = $this->createCampaignForUser($fixtures, $user);

        $this->login($client, $user);
        $client->request('POST', sprintf('/campaign/answer/bad/%d', $message->getId()), [
            'choiceId' => 1,
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    // ──────────────────────────────────────────────
    // /campaign/{campaign}/communication/{communication}/relaunch
    // ──────────────────────────────────────────────

    public function testRelaunchRequiresAdmin(): void
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());
        $user     = $fixtures->createRawUser('comm_relaunch-'.uniqid().'@example.com', 'password', false);
        [$campaign, $communication] = $this->createCampaignForUser($fixtures, $user);

        $this->login($client, $user);
        $client->request('GET', sprintf(
            '/campaign/%d/communication/%d/relaunch',
            $campaign->getId(),
            $communication->getId()
        ));

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAccessDenied()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        // Create a campaign owned by one user/structure
        $owner = $fixtures->createRawUser('comm_owner@example.com', 'password');
        [$campaign] = $this->createCampaignForUser($fixtures, $owner);

        // Create a different user with a different structure (no overlap)
        $outsider       = $fixtures->createRawUser('comm_outsider@example.com', 'password');
        $otherStructure = $fixtures->createStructure('OTHER STRUCT', 'EXT-OTHER-001');
        $fixtures->assignUserToStructure($outsider, $otherStructure);

        $this->login($client, $outsider);

        $client->request('GET', sprintf('/campaign/%d', $campaign->getId()));

        $this->assertTrue(
            $client->getResponse()->isForbidden(),
            'User without matching structures should be denied access to the campaign'
        );
    }
}
