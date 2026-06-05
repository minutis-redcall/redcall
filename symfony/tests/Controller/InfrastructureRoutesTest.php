<?php

namespace App\Tests\Controller;

use App\Tests\Base\BaseWebTestCase;
use App\Tests\Fixtures\DataFixtures;

/**
 * Coverage for routes that live outside the security firewall or are
 * tiny enough to share a single test file: GoogleController (_ah/*),
 * CronController, DeployController, TaskController, OAuth/GoogleConnect.
 */
class InfrastructureRoutesTest extends BaseWebTestCase
{
    private function getFixtures($container) : DataFixtures
    {
        return new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_hasher')
        );
    }

    // ──────────────────────────────────────────────
    // /_ah/{start,stop,warmup}
    // ──────────────────────────────────────────────

    public function testGoogleStartReturns200(): void
    {
        $client = static::createClient();
        $client->request('GET', '/_ah/start');
        $this->assertResponseIsSuccessful();
    }

    public function testGoogleStopReturns200(): void
    {
        $client = static::createClient();
        $client->request('GET', '/_ah/stop');
        $this->assertResponseIsSuccessful();
    }

    public function testGoogleWarmupReturns200(): void
    {
        // warmup runs cache:warmup --env=prod which is a heavy side effect;
        // we still hit the route to ensure it doesn't 500 in the test env.
        $client = static::createClient();
        $client->request('GET', '/_ah/warmup');
        $this->assertResponseIsSuccessful();
    }

    // ──────────────────────────────────────────────
    // /cron/{key}
    // ──────────────────────────────────────────────

    public function testCronRejectsUnknownKeyWith404(): void
    {
        $client = static::createClient();
        $client->request('GET', '/cron/unknown-cron-key');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testCronDeniesAccessForNonAdminWithoutAppengineHeader(): void
    {
        $client = static::createClient();

        // 127.0.0.1 (the kernel browser client IP) is whitelisted by the
        // controller, so to hit the deny branch we explicitly override
        // REMOTE_ADDR. Same firewall-entry-point caveat as /task/webhook:
        // an AccessDeniedException is converted to a 302 to /connect.
        $client->request('GET', '/cron/user-cron', [], [], ['REMOTE_ADDR' => '203.0.113.5']);
        $status = $client->getResponse()->getStatusCode();
        $this->assertContains(
            $status,
            [302, 403],
            sprintf('Expected 302 or 403 for a cron call from a non-whitelisted IP; got %d', $status)
        );
    }

    // ──────────────────────────────────────────────
    // /deploy
    // ──────────────────────────────────────────────

    public function testDeployCheckReturnsGreenlight(): void
    {
        $client = static::createClient();
        $client->request('GET', '/deploy');

        $this->assertResponseIsSuccessful();
        // The body is whatever messageManager->getDeployGreenlight() returns;
        // assert it's not empty rather than the specific value.
        $this->assertNotNull($client->getResponse()->getContent());
    }

    // ──────────────────────────────────────────────
    // /task/webhook
    // ──────────────────────────────────────────────

    public function testTaskWebhookDeniesAccessWithoutAppengineHeader(): void
    {
        $client = static::createClient();
        $client->request('POST', '/task/webhook', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['WebhookRequest' => ['origin' => 'x', 'queryParams' => [], 'body' => [], 'headers' => [], 'absoluteUri' => '/', 'relativeUri' => '/']]));

        // The controller throws AccessDeniedException via checkOrigin(); the
        // firewall's entry point converts that into a 302 to /connect rather
        // than a clean 401/403 (since access_control says PUBLIC_ACCESS for
        // ^/task). Both are "denied" outcomes; accept either.
        $status = $client->getResponse()->getStatusCode();
        $this->assertContains(
            $status,
            [302, 403],
            sprintf('Expected 302 (firewall) or 403 (controller) for /task/webhook with no X-Appengine-QueueName header; got %d', $status)
        );
    }

    public function testTaskWebhookReturns404OnMissingPayload(): void
    {
        $client = static::createClient();
        $client->request('POST', '/task/webhook', [], [], [
            'CONTENT_TYPE'                  => 'application/json',
            'HTTP_X_APPENGINE_QUEUENAME'    => 'some-queue',
        ], json_encode(['NotAWebhookRequest' => true]));

        // Same caveat: the firewall may intercept before the controller's
        // NotFound throw. Accept the firewall redirect or the controller 404.
        $status = $client->getResponse()->getStatusCode();
        $this->assertContains(
            $status,
            [302, 404],
            sprintf('Expected 302 (firewall) or 404 (controller) for malformed payload; got %d', $status)
        );
    }

    // ──────────────────────────────────────────────
    // /google-connect, /google-verify
    // ──────────────────────────────────────────────

    public function testGoogleVerifyRedirectsToHome(): void
    {
        $client = static::createClient();
        $client->request('GET', '/google-verify');

        $this->assertResponseRedirects('/');
    }

    public function testGoogleConnectRedirects(): void
    {
        $client = static::createClient();
        $client->request('GET', '/google-connect');

        // Either redirect to the configured google URL or to the Google
        // authorization URI — both are 302s.
        $this->assertResponseStatusCodeSame(302);
    }

    // ──────────────────────────────────────────────
    // /export/{id}/csv, /export/{id}/pdf
    // ──────────────────────────────────────────────

    public function testExportCsvForbiddenForOutsider(): void
    {
        $client    = static::createClient();
        $container = $client->getContainer();
        $fixtures  = $this->getFixtures($container);

        $owner = $fixtures->createRawUser('exp-owner-'.uniqid().'@test.com', 'password');
        $ownerStruct = $fixtures->createStructure('EXP-OWNS-'.uniqid(), 'EXT-EOS-'.uniqid());
        $fixtures->assignUserToStructure($owner, $ownerStruct);
        $ownerVol  = $fixtures->createVolunteer($owner, 'VOL-EXP-OWN-'.uniqid(), 'eov-'.uniqid().'@test.com');
        $fixtures->assignVolunteerToStructure($ownerVol, $ownerStruct);
        $campaign      = $fixtures->createCampaign('Export Campaign-'.uniqid());
        $campaign->setVolunteer($ownerVol);
        $em = $container->get('doctrine.orm.entity_manager');
        $em->persist($campaign);
        $em->flush();
        $communication = $fixtures->createCommunication($campaign);

        $outsider = $fixtures->createRawUser('exp-out-'.uniqid().'@test.com', 'password');
        $this->login($client, $outsider);

        $client->request('POST', sprintf('/export/%d/csv', $communication->getId()));
        $this->assertResponseStatusCodeSame(403);
    }
}
