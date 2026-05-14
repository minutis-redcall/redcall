<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Asserts UX/a11y invariants on the public auth surface (connect, register,
 * forgot-password, change-password, nivol). These pages are anonymous-accessible
 * so the tests do not need fixtures or login state.
 *
 * The contracts asserted here exist for very concrete reasons: password
 * managers and mobile keyboards rely on the `autocomplete` and `type`
 * attributes to fill in / show the right keyboard. Removing them silently
 * breaks user experience on every login attempt.
 */
class AuthFormAccessibilityTest extends WebTestCase
{
    public function testConnectEmailFieldHasUsernameAutocomplete(): void
    {
        $client  = static::createClient();
        $crawler = $client->request('GET', '/connect');

        $this->assertResponseIsSuccessful();
        $this->assertSame(
            1,
            $crawler->filter('#classic-login input[type="email"][autocomplete="username"]')->count(),
            'The login email field needs autocomplete="username" so password managers fill it.'
        );
    }

    public function testConnectPasswordFieldHasCurrentPasswordAutocomplete(): void
    {
        $client  = static::createClient();
        $crawler = $client->request('GET', '/connect');

        $this->assertResponseIsSuccessful();
        $this->assertSame(
            1,
            $crawler->filter('#classic-login input[type="password"][autocomplete="current-password"]')->count(),
            'The login password field needs autocomplete="current-password" so password managers fill it.'
        );
    }

    public function testRegisterEmailHasUsernameAutocomplete(): void
    {
        $client  = static::createClient();
        $crawler = $client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertSame(
            1,
            $crawler->filter('input[type="email"][autocomplete="username"]')->count(),
            'Register page email field needs autocomplete="username" so password managers offer to save the new credential.'
        );
    }

    public function testRegisterPasswordsHaveNewPasswordAutocomplete(): void
    {
        $client  = static::createClient();
        $crawler = $client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertSame(
            2,
            $crawler->filter('input[type="password"][autocomplete="new-password"]')->count(),
            'Both password fields on the register page need autocomplete="new-password" so password managers suggest a fresh strong password.'
        );
    }

    public function testForgotPasswordEmailHasUsernameAutocomplete(): void
    {
        $client  = static::createClient();
        $crawler = $client->request('GET', '/forgot-password');

        $this->assertResponseIsSuccessful();
        $this->assertSame(
            1,
            $crawler->filter('input[type="email"][autocomplete="username"]')->count(),
            'Forgot-password email field needs autocomplete="username" so browsers can pre-fill the address the user has stored.'
        );
    }
}
