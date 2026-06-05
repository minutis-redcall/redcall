<?php

namespace App\Tests\Controller\Management;

use App\Tests\Base\BaseWebTestCase;
use App\Tests\Fixtures\DataFixtures;

class ManagementHomeControllerTest extends BaseWebTestCase
{
    private function getFixtures($container) : DataFixtures
    {
        return new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_hasher')
        );
    }

    public function testAnonymousIsRedirected(): void
    {
        $client = static::createClient();
        $client->request('GET', '/management/');

        $this->assertResponseStatusCodeSame(302);
    }

    public function testManagementHomeRendersForTrustedUser(): void
    {
        $client = static::createClient();
        $user   = $this->getFixtures($client->getContainer())
                       ->createRawUser('mgmt-home-'.uniqid().'@test.com', 'password', false, true);

        $this->login($client, $user);
        $client->request('GET', '/management/');

        $this->assertResponseIsSuccessful();
        // The page exposes the management actions; assert a stable structural marker.
        $this->assertSelectorExists('h1');
    }
}
