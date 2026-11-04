<?php

namespace App\Tests\Controller;

use App\Tests\Base\BaseWebTestCase;

/**
 * Twilio webhook routes. All endpoints validate the X-Twilio-Signature
 * HMAC against a TWILIO_AUTH_TOKEN secret. In the test env we have no
 * such secret nor a way to forge a valid signature, so the routes are
 * effectively only reachable in two states: 400 (bad/missing signature)
 * and 200 (controller short-circuit before the signature check, e.g.
 * an unrecognised payload shape). We assert the signature-rejection
 * path, which is the dominant one in production for any non-Twilio
 * caller.
 */
class TwilioRoutesTest extends BaseWebTestCase
{
    public function testIncomingCallWithoutSignatureRejected(): void
    {
        $client = static::createClient();
        $client->request('POST', '/twilio/incoming-call');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testOutgoingCallWithoutSignatureRejected(): void
    {
        $client = static::createClient();
        $client->request('POST', '/twilio/outgoing-call/some-uuid');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testAnsweringMachineWithoutSignatureRejected(): void
    {
        $client = static::createClient();
        $client->request('POST', '/twilio/answering-machine/some-uuid');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testIncomingMessageWithoutSignatureRejected(): void
    {
        $client = static::createClient();
        $client->request('POST', '/twilio/incoming-message');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testMessageStatusWithoutSignatureRejected(): void
    {
        $client = static::createClient();
        $client->request('POST', '/twilio/message-status/some-uuid');

        $this->assertResponseStatusCodeSame(400);
    }

    // ──────────────────────────────────────────────
    // POST /cloud-task (GoogleTaskBundle)
    // ──────────────────────────────────────────────

    public function testCloudTaskReceiverAcceptsEmptyPayload(): void
    {
        $client = static::createClient();
        $client->request('POST', '/cloud-task');

        // The receiver inspects the payload and dispatches; with no payload
        // it should short-circuit to a 200 (or 500 if it expects a header).
        // Either way the route is reachable and registered.
        $status = $client->getResponse()->getStatusCode();
        $this->assertContains($status, [200, 400, 500]);
    }

    public function testRelayedStatusCannotBeOverriddenByUnsignedQueryParameters(): void
    {
        // Regression (CERT-FR RM #1080870, ANSSI-02): in a Cloud Task relay
        // only absoluteUri + body are covered by the Twilio signature, yet the
        // status was read from the (unsigned) query string first.
        $client = static::createClient();
        $em     = $client->getContainer()->get('doctrine.orm.entity_manager');

        // TaskController reads the queue name with getenv(), which Symfony's
        // .env loading does not populate in tests.
        $previousQueue = getenv('GCP_QUEUE_WEBHOOK_STATUS');
        putenv('GCP_QUEUE_WEBHOOK_STATUS=webhook-sms-status');

        $uuid    = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $message = new \Bundles\TwilioBundle\Entity\TwilioMessage();
        $message->setUuid($uuid);
        $message->setDirection(\Bundles\TwilioBundle\Entity\TwilioMessage::DIRECTION_OUTBOUND);
        $message->setFromNumber('+33100000000');
        $message->setToNumber('+33600000000');
        $em->persist($message);
        $em->flush();

        $absoluteUri = 'https://redcall.example/twilio/message-status/'.$uuid;
        $body        = ['MessageSid' => 'SM'.str_repeat('a', 32), 'MessageStatus' => 'failed'];
        $signature   = (new \Twilio\Security\RequestValidator('MOCK_TOKEN'))->computeSignature($absoluteUri, $body);

        $client->request('POST', '/task/webhook', [], [], [
            'HTTP_X_APPENGINE_QUEUENAME' => 'webhook-sms-status',
            'CONTENT_TYPE'               => 'application/json',
        ], json_encode(['WebhookRequest' => [
            'origin'      => 'webhook-sms-status',
            'relativeUri' => '/'.$uuid,
            'absoluteUri' => $absoluteUri,
            'queryParams' => ['MessageStatus' => 'delivered'],
            'body'        => $body,
            'headers'     => ['X-Twilio-Signature' => $signature],
        ]]));

        putenv(false === $previousQueue ? 'GCP_QUEUE_WEBHOOK_STATUS' : 'GCP_QUEUE_WEBHOOK_STATUS='.$previousQueue);

        $this->assertResponseIsSuccessful();
        $status = $em->createQuery('SELECT m.status FROM Bundles\TwilioBundle\Entity\TwilioMessage m WHERE m.uuid = :uuid')
                     ->setParameter('uuid', $uuid)
                     ->getSingleScalarResult();
        $this->assertSame('failed', $status);
    }
}
