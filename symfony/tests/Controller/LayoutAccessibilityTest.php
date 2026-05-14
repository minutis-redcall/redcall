<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Asserts UX/a11y invariants on the main base.html.twig layout.
 *
 * These tests hit anonymous-accessible pages so they exercise the layout
 * end-to-end without needing fixtures or auth. The contracts here protect
 * the improvements landed during the Twig UX review pass: if a future change
 * accidentally drops `<html lang>` or the skip-to-content link, these tests
 * will fail and the reviewer will understand why those attributes exist.
 */
class LayoutAccessibilityTest extends WebTestCase
{
    public function testBaseLayoutSetsHtmlLangAttribute(): void
    {
        $client  = static::createClient();
        $crawler = $client->request('GET', '/connect');

        $this->assertResponseIsSuccessful();
        $this->assertSame(
            1,
            $crawler->filter('html[lang]')->count(),
            'The <html> tag must declare a language so screen readers and translation tools work correctly.'
        );
    }

    /**
     * Flash messages that announce errors or warnings must use role="alert" so that
     * assistive tech reads them out as they appear. Less urgent informational flashes
     * use role="status" instead.
     */
    public function testDangerFlashRendersWithAlertRole(): void
    {
        $client    = static::createClient();
        $container = $client->getContainer();

        // Inject a flash directly into the session, then load any page that
        // extends base.html.twig — the flash should be rendered with role="alert".
        $session = $container->get('session.factory')->createSession();
        $session->getFlashBag()->add('danger', 'Flash danger sentinel for the UX a11y test.');
        $session->save();

        $cookie = new \Symfony\Component\BrowserKit\Cookie($session->getName(), $session->getId());
        $client->getCookieJar()->set($cookie);

        $crawler = $client->request('GET', '/connect');

        $this->assertResponseIsSuccessful();
        $this->assertGreaterThanOrEqual(
            1,
            $crawler->filter('.alert.alert-danger[role="alert"]')->count(),
            'Danger flashes must be announced with role="alert" so screen readers pick them up immediately.'
        );
    }

    public function testSuccessFlashRendersWithStatusRole(): void
    {
        $client    = static::createClient();
        $container = $client->getContainer();

        $session = $container->get('session.factory')->createSession();
        $session->getFlashBag()->add('success', 'Flash success sentinel for the UX a11y test.');
        $session->save();

        $cookie = new \Symfony\Component\BrowserKit\Cookie($session->getName(), $session->getId());
        $client->getCookieJar()->set($cookie);

        $crawler = $client->request('GET', '/connect');

        $this->assertResponseIsSuccessful();
        $this->assertGreaterThanOrEqual(
            1,
            $crawler->filter('.alert.alert-success[role="status"]')->count(),
            'Success flashes use role="status" — informational, non-urgent.'
        );
    }
}
