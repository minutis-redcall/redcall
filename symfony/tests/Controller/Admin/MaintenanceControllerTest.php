<?php

namespace App\Tests\Controller\Admin;

use App\Tests\Base\BaseWebTestCase;
use App\Tests\Fixtures\DataFixtures;

class MaintenanceControllerTest extends BaseWebTestCase
{
    private function getFixtures($container) : DataFixtures
    {
        return new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_hasher')
        );
    }

    public function testMaintenanceIndexAsRoot()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $root = $fixtures->createRawUser('root_maint@test.com', 'password', true);

        $this->login($client, $root);

        $crawler = $client->request('GET', '/admin/maintenance/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('a[href*="maintenance"]');
    }

    public function testMaintenanceIndexDeniedForAdmin()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        // Create an admin who is NOT root
        $admin = $fixtures->createRawUser('admin_maint@test.com', 'password', true);
        $admin->setIsRoot(false);
        $client->getContainer()->get('doctrine.orm.entity_manager')->flush();

        $this->login($client, $admin);

        $client->request('GET', '/admin/maintenance/');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testMaintenanceMessage()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $root = $fixtures->createRawUser('root_msg@test.com', 'password', true);

        $this->login($client, $root);

        $crawler = $client->request('GET', '/admin/maintenance/message');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    // Regression (CERT-FR RM #1080870, ANSSI-06): heavy syncs were triggered
    // by a plain GET link, so any page a root visited could start them.

    private function loginRoot($client): void
    {
        $fixtures = new \App\Tests\Fixtures\DataFixtures(
            $client->getContainer()->get('doctrine.orm.entity_manager'),
            $client->getContainer()->get('security.password_hasher')
        );
        $this->login($client, $fixtures->createRawUser('maint-csrf-'.uniqid().'@test.com', 'password', true));
    }

    public static function syncActionProvider(): array
    {
        return [['/admin/maintenance/data-sync'], ['/admin/maintenance/annuaire-national']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('syncActionProvider')]
    public function testSyncActionRejectsGet(string $url): void
    {
        $client = static::createClient();
        $this->loginRoot($client);

        $client->request('GET', $url);

        $this->assertResponseStatusCodeSame(405);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('syncActionProvider')]
    public function testSyncActionRejectsForgedCsrf(string $url): void
    {
        $client = static::createClient();
        $this->loginRoot($client);

        $client->request('POST', $url, ['csrf' => 'forged']);

        $this->assertResponseStatusCodeSame(404);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('syncActionProvider')]
    public function testSyncActionRunsWithValidCsrf(string $url): void
    {
        $client = static::createClient();
        $this->loginRoot($client);
        $csrf = $client->getContainer()->get('security.csrf.token_manager')->getToken('maintenance')->getValue();

        $client->request('POST', $url, ['csrf' => $csrf]);

        $this->assertResponseRedirects('/admin/maintenance/');
    }
}
