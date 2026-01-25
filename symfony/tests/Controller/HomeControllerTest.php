<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    public function testLoggedOut()
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseStatusCodeSame(302);

        $client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }
}
