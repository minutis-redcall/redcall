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

    public function testCodePageReturns404ForUnknownUuid(): void
    {
        $client = static::createClient();
        $client->request('GET', '/code/00000000-0000-0000-0000-000000000000');

        $this->assertResponseStatusCodeSame(404);
    }
}
