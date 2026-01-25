<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Tests\Base\BaseWebTestCase;
use App\Tests\Fixtures\UserFixtures;
use Bundles\PasswordLoginBundle\Manager\EmailVerificationManager;
use Bundles\SandboxBundle\Manager\FakeEmailManager;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class SecurityControllerTest extends BaseWebTestCase
{
    private function getFixtures($container) : UserFixtures
    {
        return new UserFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_encoder')
        );
    }

    public function testRegister()
    {
        $client = static::createClient();
        $client->followRedirects();

        // Ensure database is clean and whitelist current IP
        $container = $client->getContainer();
        $em        = $container->get('doctrine')->getManager();
        $em->createQuery('DELETE FROM Bundles\PasswordLoginBundle\Entity\Captcha')->execute();

        $captchaManager = $container->get(\Bundles\PasswordLoginBundle\Manager\CaptchaManager::class);
        $captchaManager->whitelistNow('127.0.0.1');

        // 1. Submit Registration Form
        $crawler = $client->request('GET', '/register');
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="registration"]')->form();

        $form['registration[username]']         = 'newuser@example.com';
        $form['registration[password][first]']  = 'SuperStrongPassword123!';
        $form['registration[password][second]'] = 'SuperStrongPassword123!';

        // Try selecting by value if it's a choice/radio
        $form['registration[platform]']->select('fr');

        $client->submit($form);

        $this->assertResponseIsSuccessful();

        // 2. Verify User Created (Unverified)
        $userRepo = $client->getContainer()->get('doctrine')->getRepository(User::class);
        $user     = $userRepo->findOneBy(['username' => 'newuser@example.com']);

        $this->assertNotNull($user, 'User should be created');
        $this->assertFalse($user->isVerified(), 'User should not be verified yet');

        // 3. Intercept Verification Email
        $fakeEmailManager = $client->getContainer()->get(FakeEmailManager::class);
        $emails           = $fakeEmailManager->findMessagesForEmail('newuser@example.com');
        $this->assertCount(1, $emails, 'Verification email should be sent');

        // 4. Extract Verification Link
        $body = $emails[0]->getBody();

        preg_match('#(http://localhost/verify-email/[a-zA-Z0-9-]+)#', $body, $matches);
        $this->assertNotEmpty($matches, 'Verification link not found in email body');
        $verificationUrl = $matches[1];

        // 5. Follow Verification Link
        $client->request('GET', $verificationUrl);

        // 6. Verify User is Verified
        $user = $client->getContainer()->get('doctrine')->getManager()->find(User::class, $user->getId());
        $this->assertTrue($user->isVerified(), 'User should be verified after clicking link');
    }

    public function testVerifyEmail()
    {
        // ... kept as specific unit/integration test if needed, or can be removed if redundant.
        // Keeping it for now as it tests the specific token logic in isolation.
        $client    = static::createClient();
        $container = $client->getContainer();

        $user = $this->getFixtures($container)->createRawUser('unverified_direct@example.com', 'password', false, false);

        $emailVerificationManager = $container->get(EmailVerificationManager::class);
        $uuid                     = $emailVerificationManager->generateToken($user->getUsername(), \Bundles\PasswordLoginBundle\Entity\EmailVerification::TYPE_REGISTRATION);

        $client->followRedirects();
        $client->request('GET', '/verify-email/'.$uuid);

        $user = $container->get('doctrine')->getRepository(User::class)->findOneByUsername('unverified_direct@example.com');
        $this->assertTrue($user->isVerified(), 'User should be verified after clicking link');
    }

    public function testConnect()
    {
        $client = static::createClient();
        $client->followRedirects();
        $this->getFixtures($client->getContainer())->createRawUser('login@example.com', 'password');

        $crawler = $client->request('GET', '/connect');

        $form             = $crawler->filter('#classic-login form')->form();
        $form['username'] = 'login@example.com';
        $form['password'] = 'password';

        $client->submit($form);

        $this->assertStringContainsString('/', $client->getRequest()->getRequestUri());
    }

    public function testForgotPassword()
    {
        $client = static::createClient();
        $client->followRedirects();

        // Cleanup DB to prevent flood protection blocking
        $container = $client->getContainer();
        $em        = $container->get('doctrine')->getManager();
        $em->createQuery('DELETE FROM Bundles\PasswordLoginBundle\Entity\PasswordRecovery')->execute();
        $em->createQuery('DELETE FROM Bundles\SandboxBundle\Entity\FakeEmail')->execute();

        $this->getFixtures($client->getContainer())->createRawUser('forgot@example.com', 'password');

        // Verify user exists
        $user = $client->getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['username' => 'forgot@example.com']);
        $this->assertNotNull($user, 'User must exist for forgot password test');

        // 1. Request Password Reset
        $crawler                           = $client->request('GET', '/forgot-password');
        $form                              = $crawler->filter('form[name="forgot_password"]')->form();
        $form['forgot_password[username]'] = 'forgot@example.com';
        $client->submit($form);

        // 2. Intercept Email
        $fakeEmailManager = $client->getContainer()->get(FakeEmailManager::class);
        $emails           = $fakeEmailManager->findMessagesForEmail('forgot@example.com');
        $this->assertCount(1, $emails, 'Recovery email should be sent');

        // 3. Extract Reset Link
        $body = $emails[0]->getBody();

        // Match relative or absolute URL
        preg_match('#(https?://[^/]+)?(/change-password/[a-zA-Z0-9-]+)#', $body, $matches);

        $this->assertNotEmpty($matches, 'Reset link not found in email body');
        // If absolute match found (index 0), use it. If only relative (index 2 implies group 2), consider constructing absolute.
        // If matches[1] is empty, it's relative.
        $resetUrl = isset($matches[0]) ? $matches[0] : '';
        // If the URL in email is just /change-password/..., construct full for client->request?
        // client->request works with relative URIs.
        if (strpos($resetUrl, 'http') !== 0) {
            // If regex matched just the path
            $resetUrl = $matches[0];
        }

        // 4. Follow Link and Change Password
        $crawler = $client->request('GET', $resetUrl);

        $form                                      = $crawler->filter('form[name="change_password"]')->form();
        $form['change_password[password][first]']  = 'newpassword123';
        $form['change_password[password][second]'] = 'newpassword123';
        $client->submit($form);

        // 5. Verify Login with New Password
        $crawler          = $client->request('GET', '/connect');
        $form             = $crawler->filter('#classic-login form')->form();
        $form['username'] = 'forgot@example.com';
        $form['password'] = 'newpassword123';
        $client->submit($form);

        $this->assertStringContainsString('/', $client->getRequest()->getRequestUri());
    }

    public function testProfile()
    {
        $client = static::createClient();
        $client->getCookieJar()->clear();
        $client->followRedirects();
        $container = static::getContainer();

        $user = $this->getFixtures(static::getContainer())->createRawUser('profile@example.com', 'password');
        $this->login($client, $user);

        $crawler = $client->request('GET', '/profile');
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="profile"]')->form();

        $form['profile[username]']         = 'profile@example.com';
        $form['profile[current_password]'] = 'password';
        $form['profile[password][first]']  = 'Upd4t3dP4ssw0rd!';
        $form['profile[password][second]'] = 'Upd4t3dP4ssw0rd!';

        $client->submit($form);

        $this->assertResponseIsSuccessful();

        // Check if password changed in DB
        $container->get('doctrine')->getManager()->clear();
        /** @var PasswordAuthenticatedUserInterface $updatedUser */
        $updatedUser = $container->get('doctrine')->getRepository(User::class)->findOneBy(['username' => 'profile@example.com']);
        $encoder     = $container->get('security.password_hasher');

        $this->assertTrue($encoder->isPasswordValid($updatedUser, 'Upd4t3dP4ssw0rd!'), 'Password should be updated in DB');
        $this->assertSelectorExists('.alert-success');
    }
}
