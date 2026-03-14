<?php

namespace App\Tests\Manager;

use App\Manager\LocaleManager;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class LocaleManagerTest extends KernelTestCase
{
    private LocaleManager $manager;
    private SessionInterface $session;
    private RequestStack $requestStack;
    private TokenStorageInterface $tokenStorage;

    protected function setUp() : void
    {
        self::bootKernel();

        $container = static::getContainer();

        $this->session      = $container->get('session');
        $this->requestStack = $container->get('request_stack');
        $this->tokenStorage = $container->get('security.token_storage');
        $this->manager      = $container->get(LocaleManager::class);
    }

    private function pushRequest(string $locale = 'en') : Request
    {
        $request = Request::create('/');
        $request->setLocale($locale);
        $request->setSession($this->session);
        $this->requestStack->push($request);

        return $request;
    }

    public function testChangeLocaleSetsLocaleOnRequestAndSession()
    {
        $request = $this->pushRequest('en');

        $this->manager->changeLocale('fr');

        $this->assertSame('fr', $request->getLocale());
        $this->assertSame('fr', $this->session->get('_locale'));
    }

    public function testChangeLocaleSanitizesInvalidLocaleToDefault()
    {
        $request = $this->pushRequest('en');

        $this->manager->changeLocale('xx_invalid');

        // 'xx' is not in the available locales, so it should fall back to default 'en'
        $this->assertSame('en', $request->getLocale());
        $this->assertSame('en', $this->session->get('_locale'));
    }

    public function testChangeLocaleStripsRegionSuffix()
    {
        $request = $this->pushRequest('en');

        $this->manager->changeLocale('fr_FR');

        // Should strip '_FR' and use 'fr'
        $this->assertSame('fr', $request->getLocale());
        $this->assertSame('fr', $this->session->get('_locale'));
    }

    public function testChangeLocaleIsCaseInsensitive()
    {
        $request = $this->pushRequest('en');

        $this->manager->changeLocale('FR');

        $this->assertSame('fr', $request->getLocale());
        $this->assertSame('fr', $this->session->get('_locale'));
    }

    public function testRestoreFromSessionUsesSessionLocale()
    {
        $request = $this->pushRequest('en');
        $this->session->set('_locale', 'fr');

        $this->manager->restoreFromSession();

        $this->assertSame('fr', $request->getLocale());
    }

    public function testRestoreFromSessionUsesRequestAttributeWhenNoSessionLocale()
    {
        $request = $this->pushRequest('en');
        $this->session->remove('_locale');
        $request->attributes->set('_locale', 'fr');

        $this->manager->restoreFromSession();

        $this->assertSame('fr', $request->getLocale());
    }

    public function testRestoreFromSessionFallsBackToDefaultLocale()
    {
        $request = $this->pushRequest('xx');
        $this->session->remove('_locale');
        $request->attributes->remove('_locale');

        $this->manager->restoreFromSession();

        // Default locale is 'en' (from parameters.yaml)
        $this->assertSame('en', $request->getLocale());
    }

    public function testRestoreFromUserSetsLocaleFromUserPreferences()
    {
        $container = static::getContainer();
        $fixtures  = new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_encoder')
        );

        $user = $fixtures->createRawUser('locale_test@example.com');
        $user->setLocale('fr');
        $container->get('doctrine.orm.entity_manager')->persist($user);
        $container->get('doctrine.orm.entity_manager')->flush();

        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->tokenStorage->setToken($token);

        $request = $this->pushRequest('en');

        $this->manager->restoreFromUser();

        $this->assertSame('fr', $request->getLocale());
    }

    public function testRestoreFromUserDoesNothingWhenNoUserLoggedIn()
    {
        $this->tokenStorage->setToken(null);
        $request = $this->pushRequest('en');

        $this->manager->restoreFromUser();

        // Locale should remain unchanged
        $this->assertSame('en', $request->getLocale());
    }

    public function testSaveChangesLocaleAndPersistsToUser()
    {
        $container = static::getContainer();
        $fixtures  = new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_encoder')
        );

        $user = $fixtures->createRawUser('save_locale_test@example.com');

        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->tokenStorage->setToken($token);

        $request = $this->pushRequest('en');

        $this->manager->save('fr');

        $this->assertSame('fr', $request->getLocale());
        $this->assertSame('fr', $this->session->get('_locale'));
        $this->assertSame('fr', $user->getLocale());
    }

    public function testSaveChangesLocaleWithoutUserWhenNotLoggedIn()
    {
        $this->tokenStorage->setToken(null);
        $request = $this->pushRequest('en');

        $this->manager->save('fr');

        $this->assertSame('fr', $request->getLocale());
        $this->assertSame('fr', $this->session->get('_locale'));
    }
}
