<?php

namespace App\Tests\Manager;

use App\Manager\MailManager;
use Bundles\SandboxBundle\Entity\FakeEmail;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MailManagerTest extends KernelTestCase
{
    private MailManager $manager;

    protected function setUp() : void
    {
        self::bootKernel();
        $this->manager = static::getContainer()->get(MailManager::class);
    }

    public function testSimpleSendsEmail()
    {
        $container = static::getContainer();

        $this->manager->simple(
            'recipient@example.com',
            'Test Subject',
            'Plain text body',
            '<p>HTML body</p>',
            'fr'
        );

        // In test env, FakeEmailProvider stores emails via the FakeEmail repository
        $em         = $container->get('doctrine.orm.entity_manager');
        $fakeEmails = $em->getRepository(FakeEmail::class)->findAll();

        $this->assertNotEmpty($fakeEmails);

        // Find the email we just sent - FakeEmail uses getEmail() for recipient
        $found = false;
        foreach ($fakeEmails as $email) {
            if ($email->getEmail() === 'recipient@example.com') {
                $found = true;
                $this->assertStringContainsString('Test Subject', $email->getSubject());
                // Body should be the rendered Twig template containing the HTML content
                $this->assertStringContainsString('HTML body', $email->getBody());
                break;
            }
        }

        $this->assertTrue($found, 'Expected email to recipient@example.com was not found');
    }

    public function testSimpleWithEnglishLocale()
    {
        $container = static::getContainer();

        $this->manager->simple(
            'english@example.com',
            'English Subject',
            'Plain text',
            '<p>English HTML</p>',
            'en'
        );

        $em         = $container->get('doctrine.orm.entity_manager');
        $fakeEmails = $em->getRepository(FakeEmail::class)->findAll();

        $found = false;
        foreach ($fakeEmails as $email) {
            if ($email->getEmail() === 'english@example.com') {
                $found = true;
                $this->assertStringContainsString('English Subject', $email->getSubject());
                break;
            }
        }

        $this->assertTrue($found, 'Expected email to english@example.com was not found');
    }
}
