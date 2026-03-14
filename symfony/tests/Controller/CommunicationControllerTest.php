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
            $container->get('security.password_encoder')
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
