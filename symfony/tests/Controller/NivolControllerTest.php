<?php

namespace App\Tests\Controller;

use App\Tests\Base\BaseWebTestCase;
use App\Tests\Fixtures\DataFixtures;

class NivolControllerTest extends BaseWebTestCase
{
    private function getFixtures($container) : DataFixtures
    {
        return new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_hasher')
        );
    }

    public function testNivolLoginPageRenders(): void
    {
        $client = static::createClient();
        $client->request('GET', '/nivol');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testCodePageRendersForUnknownUuid(): void
    {
        // Regression (CERT-FR RM #1080870, audit M9): a 404 here told apart the
        // NIVOLs that have a RedCall account from those that don't.
        $client = static::createClient();
        $client->request('GET', '/code/00000000-0000-0000-0000-000000000000');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }
}
