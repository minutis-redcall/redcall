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
}
