<?php

namespace App\Tests\Controller\Admin;

use App\Tests\Base\BaseWebTestCase;
use App\Tests\Fixtures\DataFixtures;

class GdprControllerTest extends BaseWebTestCase
{
    private function getFixtures($container) : DataFixtures
    {
        return new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_hasher')
        );
    }

    public function testHistoryAccessDeniedForNonAdmin()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $user = $fixtures->createRawUser('gdpr_non_admin@test.com', 'password', false);
        $this->login($client, $user);

        $client->request('GET', '/admin/gdpr/history');

        $this->assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testEmptyHistoryRendersForAdmin()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin = $fixtures->createRawUser('gdpr_empty_admin@test.com', 'password', true);
        $this->login($client, $admin);

        $client->request('GET', '/admin/gdpr/history');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Journal des suppressions');
    }

    public function testHistoryRendersNivolAndActor()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin = $fixtures->createRawUser('gdpr_render_admin@test.com', 'password', true);
        $data  = $fixtures->createUserWithVolunteerAndStructure('gdpr_render_target@test.com', false);

        $fixtures->createVolunteerAuditLog(
            $admin,
            $data['volunteer'],
            'anonymize',
            'admin: manual',
            ['externalId' => $data['volunteer']->getExternalId(), 'isLocked' => false, 'isEnabled' => true, 'isMinor' => false, 'hadBoundUser' => true, 'structures' => [], 'badges' => []],
            $data['user']->getId()
        );

        $this->login($client, $admin);

        $client->request('GET', '/admin/gdpr/history');
        $this->assertResponseIsSuccessful();
        // NIVOL must be visible
        $this->assertSelectorTextContains('body', $data['volunteer']->getExternalId());
        // bound user UUID must be visible
        $this->assertSelectorTextContains('body', $data['user']->getId());
    }

    public function testSearchByNivol()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin = $fixtures->createRawUser('gdpr_search_admin@test.com', 'password', true);
        $hit   = $fixtures->createUserWithVolunteerAndStructure('gdpr_hit@test.com', false, 'HIT-SEARCH-9911', 'HIT STR', 'HIT-STR-1');
        $miss  = $fixtures->createUserWithVolunteerAndStructure('gdpr_miss@test.com', false, 'MISS-SEARCH-2233', 'MISS STR', 'MISS-STR-1');

        $fixtures->createVolunteerAuditLog($admin, $hit['volunteer'], 'anonymize', 'admin: manual', ['externalId' => 'HIT-SEARCH-9911']);
        $fixtures->createVolunteerAuditLog($admin, $miss['volunteer'], 'anonymize', 'admin: manual', ['externalId' => 'MISS-SEARCH-2233']);

        $this->login($client, $admin);

        $client->request('GET', '/admin/gdpr/history?form%5Bcriteria%5D=HIT-SEARCH');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'HIT-SEARCH-9911');
        $this->assertSelectorTextNotContains('body', 'MISS-SEARCH-2233');
    }

    public function testHideTechnicalFiltersOutSyncRows()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin = $fixtures->createRawUser('gdpr_filter_admin@test.com', 'password', true);
        $a     = $fixtures->createUserWithVolunteerAndStructure('gdpr_filter_a@test.com', false, 'MANUAL-FILTER-1', 'A STR', 'A-STR-1');
        $b     = $fixtures->createUserWithVolunteerAndStructure('gdpr_filter_b@test.com', false, 'SYNC-FILTER-2', 'B STR', 'B-STR-1');

        $fixtures->createVolunteerAuditLog($admin, $a['volunteer'], 'anonymize', 'admin: manual', ['externalId' => 'MANUAL-FILTER-1']);
        $fixtures->createVolunteerAuditLog(null, $b['volunteer'], 'anonymize', 'sync: stale', ['externalId' => 'SYNC-FILTER-2']);

        $this->login($client, $admin);

        $client->request('GET', '/admin/gdpr/history?form%5BhideTechnical%5D=1');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'MANUAL-FILTER-1');
        $this->assertSelectorTextNotContains('body', 'SYNC-FILTER-2');
    }

    public function testHistoryPagerLinksCarryFormQueryParamsAcrossPages()
    {
        // Same bug as UserAuditLogController: unchecked hideTechnical is
        // indistinguishable from "form not submitted" in the GET payload, so
        // dropping the form params on pager links re-applied the default state.
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin = $fixtures->createRawUser('gdpr_pager_admin@test.com', 'password', true);

        for ($i = 0; $i < 25; $i++) {
            $data = $fixtures->createUserWithVolunteerAndStructure(
                sprintf('gdpr_pager_%02d@test.com', $i), false,
                sprintf('GDPR-PAGER-%02d', $i),
                sprintf('GDPR PAGER STR %02d', $i),
                sprintf('GDPR-PAGER-STR-%02d', $i)
            );
            $fixtures->createVolunteerAuditLog(
                $admin, $data['volunteer'], 'anonymize', 'admin: manual',
                ['externalId' => $data['volunteer']->getExternalId()]
            );
        }

        $this->login($client, $admin);

        $crawler = $client->request(
            'GET',
            '/admin/gdpr/history?form%5Bcriteria%5D=GDPR-PAGER&form%5BhideTechnical%5D=&form%5Bsubmit%5D='
        );
        $this->assertResponseIsSuccessful();

        $page2Links = $crawler->filter('a[href*="page=2"]');
        $this->assertGreaterThan(0, $page2Links->count(),
            'Pager must render a page 2 link when there are > 20 matches'
        );

        $href = $page2Links->first()->attr('href');
        $this->assertMatchesRegularExpression(
            '#form(?:%5B|\[)criteria(?:%5D|\])=GDPR-PAGER#',
            $href,
            'Page 2 URL must carry the form criteria so the filter state survives navigation'
        );
    }

    public function testHistoryButtonOnGdprIndex()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin = $fixtures->createRawUser('gdpr_button_admin@test.com', 'password', true);
        $this->login($client, $admin);

        $crawler = $client->request('GET', '/admin/gdpr');
        $this->assertResponseIsSuccessful();

        $link = $crawler->selectLink('Journal des suppressions');
        $this->assertGreaterThan(0, $link->count(), 'History log button must be on the GDPR index page');
    }
}
