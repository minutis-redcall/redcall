<?php

namespace App\Tests\Controller;

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
}
