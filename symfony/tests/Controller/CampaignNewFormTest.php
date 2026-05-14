<?php

namespace App\Tests\Controller;

use App\Entity\Campaign;
use App\Tests\Base\BaseWebTestCase;
use App\Tests\Fixtures\DataFixtures;

/**
 * Smoke tests for the campaign-creation form flow at /campaign/new/{type}.
 *
 * These pages compose multiple template includes that pass a MyCLabs
 * `Type` enum down through `_context` and then call
 * `render(controller(WidgetController::templateDropdown, {type: type}))`.
 * The composition is fragile across Symfony upgrades — a regression here
 * was reported manually and not caught by the existing suite, hence
 * this dedicated smoke check.
 */
class CampaignNewFormTest extends BaseWebTestCase
{
    private function getFixtures($container) : DataFixtures
    {
        return new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_hasher')
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('campaignTypeProvider')]
    public function testCampaignNewRendersForType(string $type): void
    {
        $client    = static::createClient();
        $container = $client->getContainer();

        $setup = $this->getFixtures($container)->createUserWithVolunteerAndStructure(
            sprintf('campaign-new-%s@test.com', $type),
            false,
            sprintf('VOL-NEW-%s', strtoupper($type)),
            sprintf('NEW %s STRUCT', strtoupper($type)),
            sprintf('EXT-NEW-%s', strtoupper($type))
        );

        $this->login($client, $setup['user']);

        $client->request('GET', '/campaign/new/'.$type);

        $this->assertResponseIsSuccessful(
            sprintf('GET /campaign/new/%s should render the first step of the form flow without raising an exception.', $type)
        );
    }

    public static function campaignTypeProvider(): array
    {
        return [
            'sms'   => ['sms'],
            'call'  => ['call'],
            'email' => ['email'],
        ];
    }

    /**
     * End-to-end form submission of a new SMS campaign through CraueFormFlow.
     *
     * The campaign-creation flow has been seen to throw
     * `NotFoundHttpException("New communication has no message")` at the
     * point CampaignManager::launchNewCampaign reaches its message-count
     * guard — this is the http-layer regression test for that path.
     *
     * The fixtures emulate exactly what the UI submits when a user picks
     * the "test on me" path: a trigger pointed at their own volunteer,
     * a one-line SMS body, free-form (no choices).
     */
    public function testSubmitNewSmsCampaignTestOnMeCreatesCampaign(): void
    {
        $client    = static::createClient();
        $container = $client->getContainer();

        $setup = $this->getFixtures($container)->createUserWithVolunteerAndStructure(
            'sms_submit_tom@test.com',
            false,
            'VOL-SMS-SUBMIT-TOM',
            'STRUCT SMS SUBMIT TOM',
            'EXT-STRUCT-SMS-SUB-TOM'
        );

        $this->login($client, $setup['user']);

        $crawler = $client->request('GET', '/campaign/new/sms');
        $this->assertResponseIsSuccessful();

        // Submit the SMS-trigger step. CraueFormFlow exposes the step number
        // through a hidden form field named `flow_smsTrigger_step` (the same
        // name used by the production form).
        $form = $crawler->filter('form[name="campaign"]')->form();

        $values = [
            'campaign[label]'                          => 'E2E SMS test',
            'campaign[type]'                           => Campaign::TYPE_GREEN,
            'campaign[trigger][audience][test_on_me]'  => '1',
            'campaign[trigger][language]'              => 'fr',
            'campaign[trigger][message]'               => 'Hello from the integration test.',
            'campaign[trigger][multipleAnswer]'        => '0',
        ];

        $client->submit($form, $values);

        $this->assertNotSame(
            500,
            $client->getResponse()->getStatusCode(),
            'Submitting a valid new-SMS-campaign must not 500 — the audience pipeline should resolve "test on me" to the current user\'s volunteer.'
        );

        // The happy path redirects to /communication/{id} (success) and not
        // back to / (controller returns null when launchNewCampaign fails).
        $this->assertResponseRedirects(
            null,
            302,
            'Expected a 302 redirect to the new communication page on success.'
        );
        $location = $client->getResponse()->headers->get('Location');
        $this->assertStringNotContainsString(
            '/home',
            (string) $location,
            'A redirect to /home means launchNewCampaign returned null — the audience pipeline dropped every recipient.'
        );
        $this->assertNotSame(
            '/',
            (string) $location,
            'A redirect to / means launchNewCampaign returned null — the audience pipeline dropped every recipient.'
        );

        // Campaign should be persisted.
        $em = $container->get('doctrine.orm.entity_manager');
        $em->clear();
        $campaign = $em->getRepository(Campaign::class)->findOneBy(['label' => 'E2E SMS test']);
        $this->assertNotNull(
            $campaign,
            'Campaign entity must be persisted after a successful new-SMS-campaign submission.'
        );
    }

    /**
     * End-to-end submission with an audience picked through the volunteer
     * widget (the volunteers hidden field is filled with one or more
     * volunteer IDs). This is the most common production path and the one
     * the user reported failing with "New communication has no message".
     */
    public function testSubmitNewSmsCampaignWithVolunteerAudienceCreatesCampaign(): void
    {
        $client    = static::createClient();
        $container = $client->getContainer();

        $fixtures = $this->getFixtures($container);
        $setup    = $fixtures->createUserWithVolunteerAndStructure(
            'sms_submit_aud@test.com',
            false,
            'VOL-SMS-SUBMIT-AUD',
            'STRUCT SMS SUBMIT AUD',
            'EXT-STRUCT-SMS-SUB-AUD'
        );

        // Target volunteer in the same structure as the sender so the voter
        // gives access and `filterInaccessibles` keeps them in the audience.
        $target = $fixtures->createStandaloneVolunteer('VOL-SMS-AUDIENCE', 'sms-audience-target@test.com');
        $fixtures->assignVolunteerToStructure($target, $setup['structure']);

        $this->login($client, $setup['user']);

        $crawler = $client->request('GET', '/campaign/new/sms');
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="campaign"]')->form();

        // Mirror what AudienceType's view-layer JS does when the user picks
        // a volunteer from the flexdatalist widget: it populates the hidden
        // `volunteers` input with the volunteer's id (comma-separated when
        // there are multiple).
        $values = [
            'campaign[label]'                            => 'E2E SMS audience',
            'campaign[type]'                             => Campaign::TYPE_GREEN,
            'campaign[trigger][audience][volunteers]'    => (string) $target->getId(),
            'campaign[trigger][language]'                => 'fr',
            'campaign[trigger][message]'                 => 'Hello picked volunteer.',
            'campaign[trigger][multipleAnswer]'          => '0',
        ];

        $client->submit($form, $values);

        $status = $client->getResponse()->getStatusCode();
        $this->assertNotSame(
            500,
            $status,
            sprintf('SMS campaign submit must not 500. Got %d. Response body excerpt: %s',
                $status,
                substr(strip_tags($client->getResponse()->getContent()), 0, 200)
            )
        );
        $this->assertNotSame(
            404,
            $status,
            'SMS campaign submit must not 404 — the "New communication has no message" guard should not fire when the user picked a real volunteer.'
        );

        $em = $container->get('doctrine.orm.entity_manager');
        $em->clear();
        $campaign = $em->getRepository(Campaign::class)->findOneBy(['label' => 'E2E SMS audience']);
        $this->assertNotNull(
            $campaign,
            'Campaign with the submitted label must be persisted after a successful submission.'
        );
        $this->assertCount(1, $campaign->getCommunications(), 'Campaign must have one communication.');
        $this->assertGreaterThanOrEqual(
            1,
            $campaign->getCommunications()->first()->getMessageCount(),
            'Communication must have at least one message — the picked volunteer should not be filtered out.'
        );
    }
}
