<?php

namespace App\Tests\Controller;

use App\Entity\Communication;
use App\Tests\Base\BaseWebTestCase;
use App\Tests\Fixtures\DataFixtures;

class MessageControllerTest extends BaseWebTestCase
{
    private function getFixtures($container) : DataFixtures
    {
        return new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_encoder')
        );
    }

    /**
     * Creates a full message chain: user -> volunteer -> structure -> campaign -> communication -> message.
     * Returns the Message entity.
     */
    private function createMessageFixture($container)
    {
        $fixtures = $this->getFixtures($container);

        $user          = $fixtures->createRawUser('msg_user@example.com', 'password');
        $volunteer     = $fixtures->createVolunteer($user, 'MSG-VOL-001', 'msg_user@example.com');
        $campaign      = $fixtures->createCampaign('Message Test Campaign');
        $communication = $fixtures->createCommunication($campaign, Communication::TYPE_SMS, 'Hello volunteer!');
        $message       = $fixtures->createMessage($communication, $volunteer);

        return $message;
    }

    public function testMessageOpen()
    {
        $client    = static::createClient();
        $container = $client->getContainer();

        $message = $this->createMessageFixture($container);
        $code    = $message->getCode();

        $client->request('GET', sprintf('/msg/%s', $code));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('#content');
        $this->assertSelectorExists('form');
    }

    public function testMessageOptout()
    {
        $client    = static::createClient();
        $container = $client->getContainer();

        $message = $this->createMessageFixture($container);
        $code    = $message->getCode();

        $client->request('GET', sprintf('/msg/optout/%s', $code));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testMessageInvalidCode()
    {
        $client = static::createClient();

        $client->request('GET', '/msg/invalidcode123');

        $this->assertResponseStatusCodeSame(404);
    }
}
