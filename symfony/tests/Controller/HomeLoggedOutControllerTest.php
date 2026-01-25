<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeLoggedOutControllerTest extends WebTestCase
{
    public function testRedirectToLogin()
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseStatusCodeSame(302);
        
        $client->followRedirect();
        
        // Assert we are on the login page or entry point
        // Minimally check we are not 500 and likely on /auth
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form'); // Very basic check for a login form
    }
}
