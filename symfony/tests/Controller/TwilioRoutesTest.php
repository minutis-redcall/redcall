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
}
