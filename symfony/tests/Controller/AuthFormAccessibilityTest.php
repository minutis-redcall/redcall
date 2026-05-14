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

    public function testConnectMethodSelectorButtonsAreNotSubmitButtons(): void
    {
        $client  = static::createClient();
        $crawler = $client->request('GET', '/connect');

        $this->assertResponseIsSuccessful();
        // The two method-switcher buttons inside #auth-selector must be type="button".
        // Without that they default to type="submit" if a parent <form> exists, which
        // would post a malformed request when the user just wanted to switch panels.
        $this->assertSame(
            2,
            $crawler->filter('#auth-selector button[type="button"]')->count(),
            'Login method selector buttons must declare type="button" so they never accidentally submit a form.'
        );
    }

    public function testNivolFieldAutofocusedAndDoesNotAutocompleteAsPassword(): void
    {
        $client  = static::createClient();
        $crawler = $client->request('GET', '/connect');

        $this->assertResponseIsSuccessful();
        $this->assertSame(
            1,
            $crawler->filter('#nivol input[name="nivol"][autocomplete="off"][autofocus]')->count(),
            'Nivol field: autofocus when the user opens the NIVOL panel, autocomplete="off" so password managers do not try to fill it.'
        );
    }

    public function testProfilePageDeclaresAutocompleteOnCredentialFields(): void
    {
        $client    = static::createClient();
        $container = $client->getContainer();

        $fixtures = new \App\Tests\Fixtures\DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_hasher')
        );
        $user = $fixtures->createRawUser('profile-a11y@example.com', 'password');

        // Login helper from BaseWebTestCase. Re-implement inline to avoid the
        // dependency since this file extends plain WebTestCase.
        $client->loginUser($user);

        $crawler = $client->request('GET', '/profile');

        $this->assertResponseIsSuccessful();
        $this->assertSame(
            1,
            $crawler->filter('input[type="password"][autocomplete="current-password"]')->count(),
            'Profile page must mark the current password as "current-password" so it autofills from a saved login.'
        );
        $this->assertSame(
            2,
            $crawler->filter('input[type="password"][autocomplete="new-password"]')->count(),
            'Profile page must mark the repeated new password fields as "new-password" — password managers should not autofill the old password into them.'
        );
        $this->assertSame(
            1,
            $crawler->filter('input[type="email"][autocomplete="username"]')->count(),
            'Profile page email field is the username — declare autocomplete="username".'
        );
    }

    public function testChangePasswordFieldsHaveNewPasswordAutocomplete(): void
    {
        $client    = static::createClient();
        $container = $client->getContainer();

        // Need a real user — /change-password 404s if the token's username
        // doesn't resolve to an existing account.
        $fixtures = new \App\Tests\Fixtures\DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_hasher')
        );
        $fixtures->createRawUser('change-pw-a11y@example.com', 'password');

        $repo = $container->get(\Bundles\PasswordLoginBundle\Repository\PasswordRecoveryRepository::class);
        $uuid = $repo->generateToken('change-pw-a11y@example.com');

        $crawler = $client->request('GET', '/change-password/'.$uuid);

        $this->assertResponseIsSuccessful();
        $this->assertSame(
            2,
            $crawler->filter('input[type="password"][autocomplete="new-password"]')->count(),
            'Both fields on /change-password need autocomplete="new-password" so password managers do not autofill the old password and do offer to save the new one.'
        );
    }
}
