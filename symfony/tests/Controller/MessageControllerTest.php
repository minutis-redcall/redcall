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
            $container->get('security.password_hasher')
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

    // ──────────────────────────────────────────────
    // GET /msg/{code}/{signature}/{action}
    // ──────────────────────────────────────────────

    public function testMessageActionRecordsAnswerAndRedirects(): void
    {
        $client    = static::createClient();
        $container = $client->getContainer();

        $message = $this->createMessageFixture($container);
        // The fixture's prefix is random and may contain digits; the
        // controller's regex /^([a-zA-Z]+)(\d)/ would then drop trailing
        // digits and produce a mismatch. Force a deterministic alphabetic
        // prefix so the choice resolves cleanly.
        $message->setPrefix('AB');
        $em = $container->get('doctrine.orm.entity_manager');
        $em->persist($message);
        $em->flush();

        $fixtures = $this->getFixtures($container);
        $fixtures->createChoice($message->getCommunication(), 'Yes', '1');

        $client->request('GET', sprintf('/msg/%s/%s/1', $message->getCode(), $message->getSignature()));

        $this->assertResponseRedirects('/msg/'.$message->getCode());
    }

    public function testMessageActionRejectsBadSignature(): void
    {
        $client    = static::createClient();
        $container = $client->getContainer();

        $message  = $this->createMessageFixture($container);
        $fixtures = $this->getFixtures($container);
        $fixtures->createChoice($message->getCommunication(), 'Yes', '1');

        $client->request('GET', sprintf('/msg/%s/bad-sig/1', $message->getCode()));

        $this->assertResponseStatusCodeSame(404);
    }

    public function testMessageActionRejectsUnknownChoice(): void
    {
        $client    = static::createClient();
        $container = $client->getContainer();

        $message = $this->createMessageFixture($container);

        $client->request('GET', sprintf('/msg/%s/%s/9', $message->getCode(), $message->getSignature()));

        $this->assertResponseStatusCodeSame(404);
    }

    // ──────────────────────────────────────────────
    // GET /msg/{code}/annuler/{signature}/{action}
    // ──────────────────────────────────────────────

    public function testMessageCancelRedirectsBackToOpen(): void
    {
        $client    = static::createClient();
        $container = $client->getContainer();

        $message = $this->createMessageFixture($container);
        $message->setPrefix('AB');
        $em = $container->get('doctrine.orm.entity_manager');
        $em->persist($message);
        $em->flush();

        $fixtures = $this->getFixtures($container);
        $fixtures->createChoice($message->getCommunication(), 'Yes', '1');

        $client->request('GET', sprintf('/msg/%s/annuler/%s/1', $message->getCode(), $message->getSignature()));

        $this->assertResponseRedirects('/msg/'.$message->getCode());
    }

    public function testMessageCancelRejectsBadSignature(): void
    {
        $client    = static::createClient();
        $container = $client->getContainer();

        $message  = $this->createMessageFixture($container);
        $fixtures = $this->getFixtures($container);
        $fixtures->createChoice($message->getCommunication(), 'Yes', '1');

        $client->request('GET', sprintf('/msg/%s/annuler/wrong/1', $message->getCode()));

        $this->assertResponseStatusCodeSame(404);
    }
}
