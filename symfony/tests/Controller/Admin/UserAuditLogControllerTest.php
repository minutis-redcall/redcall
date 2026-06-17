<?php

namespace App\Tests\Controller\Admin;

use App\Entity\UserAuditLog;
use App\Tests\Base\BaseWebTestCase;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class UserAuditLogControllerTest extends BaseWebTestCase
{
    private function getFixtures($container) : DataFixtures
    {
        return new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_hasher')
        );
    }

    private function getCsrfToken($container) : string
    {
        /** @var CsrfTokenManagerInterface $tokenManager */
        $tokenManager = $container->get('security.csrf.token_manager');

        if (!$container->get('request_stack')->getMainRequest()) {
            $req = \Symfony\Component\HttpFoundation\Request::create('/');
            $req->setSession(new \Symfony\Component\HttpFoundation\Session\Session(new \Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage()));
            $container->get('request_stack')->push($req);
        }

        return $tokenManager->getToken('pegass')->getValue();
    }

    public function testAccessDeniedForNonAdmin()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $user = $fixtures->createRawUser('audit_non_admin@test.com', 'password', false);
        $this->login($client, $user);

        $client->request('GET', '/admin/redcall-users/history');

        $this->assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testEmptyHistory()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin = $fixtures->createRawUser('audit_empty_admin@test.com', 'password', true);
        $this->login($client, $admin);

        $client->request('GET', '/admin/redcall-users/history');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Journal des actions');
    }

    public function testToggleAdminRecordsAuditEntry()
    {
        $client = static::createClient();
        $client->followRedirects();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin  = $fixtures->createRawUser('audit_toggle_admin@test.com', 'password', true);
        $target = $fixtures->createRawUser('audit_toggle_target@test.com', 'password', false);

        $this->login($client, $admin);
        $csrf = $this->getCsrfToken($client->getContainer());

        $client->request('GET', sprintf('/admin/redcall-users/toggle-admin/%s/%s', $csrf, $target->getId()));
        $this->assertResponseIsSuccessful();

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();

        $logs = $em->getRepository(UserAuditLog::class)->findBy([
            'targetUsername' => 'audit_toggle_target@test.com',
        ]);

        $this->assertCount(1, $logs);
        $entry = $logs[0];
        $this->assertSame('update', $entry->getAction());
        $this->assertSame('audit_toggle_admin@test.com', $entry->getActor() ? $entry->getActor()->getUsername() : null);
        $snapshot = $entry->getSnapshot();
        $this->assertArrayHasKey('old', $snapshot);
        $this->assertArrayHasKey('new', $snapshot);
        $this->assertFalse($snapshot['old']['isAdmin']);
        $this->assertTrue($snapshot['new']['isAdmin']);
    }

    public function testDeleteRecordsAuditEntryWithDenormalisedTarget()
    {
        $client = static::createClient();
        $client->followRedirects();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin  = $fixtures->createRawUser('audit_del_admin@test.com', 'password', true);
        $target = $fixtures->createRawUser('audit_del_target@test.com', 'password', false);

        $this->login($client, $admin);
        $csrf = $this->getCsrfToken($client->getContainer());

        $client->request('GET', sprintf('/admin/redcall-users/delete/%s/%s', $csrf, $target->getId()));
        $this->assertResponseIsSuccessful();

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();

        $logs = $em->getRepository(UserAuditLog::class)->findBy([
            'targetUsername' => 'audit_del_target@test.com',
        ]);

        $this->assertCount(1, $logs);
        $entry = $logs[0];
        $this->assertSame('delete', $entry->getAction());
        $this->assertNull($entry->getTargetUser(), 'Target FK should be null after hard-delete');
        $this->assertSame('audit_del_target@test.com', $entry->getTargetUsername());
    }

    public function testSearchByTargetUsername()
    {
        $client = static::createClient();
        $client->followRedirects();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin = $fixtures->createRawUser('audit_search_admin@test.com', 'password', true);
        $hit   = $fixtures->createRawUser('searchable_unique_hit@test.com', 'password', false);
        $miss  = $fixtures->createRawUser('searchable_unique_miss@test.com', 'password', false);

        $fixtures->createUserAuditLog($admin, $hit, 'update', null, ['note' => 'hit']);
        $fixtures->createUserAuditLog($admin, $miss, 'update', null, ['note' => 'miss']);

        $this->login($client, $admin);

        $client->request('GET', '/admin/redcall-users/history?form%5Bcriteria%5D=searchable_unique_hit');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'searchable_unique_hit@test.com');
        $this->assertSelectorTextNotContains('body', 'searchable_unique_miss@test.com');
    }

    public function testSearchByActorUsername()
    {
        $client = static::createClient();
        $client->followRedirects();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin   = $fixtures->createRawUser('audit_searchactor_admin@test.com', 'password', true);
        $other   = $fixtures->createRawUser('signature_actor_xyz@test.com', 'password', true);
        $target1 = $fixtures->createRawUser('audit_searchactor_t1@test.com', 'password', false);
        $target2 = $fixtures->createRawUser('audit_searchactor_t2@test.com', 'password', false);

        $fixtures->createUserAuditLog($other, $target1, 'update');
        $fixtures->createUserAuditLog($admin, $target2, 'update');

        $this->login($client, $admin);

        $client->request('GET', '/admin/redcall-users/history?form%5Bcriteria%5D=signature_actor_xyz');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'audit_searchactor_t1@test.com');
        $this->assertSelectorTextNotContains('body', 'audit_searchactor_t2@test.com');
    }

    public function testPagerLinksCarryFormQueryParamsAcrossPages()
    {
        // Regression: pager links used to drop the form payload, so jumping to
        // page 2 silently re-enabled `hideTechnical` (an unchecked GET checkbox
        // is indistinguishable from "form not submitted at all", which falls
        // back to the default = true). The pager macro now receives queryParams
        // from the controller — every page link should include the current
        // form fields so the filter state survives pagination.
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin = $fixtures->createRawUser('pager_admin@test.com', 'password', true);

        // Need > 20 (PaginationManager::perPage default) rows whose criteria
        // matches the search marker so the pager actually renders.
        for ($i = 0; $i < 25; $i++) {
            $target = $fixtures->createRawUser(sprintf('PAGER_MARKER_777_%02d@test.com', $i), 'password', false);
            $fixtures->createUserAuditLog($admin, $target, 'update', null, ['note' => 'marker']);
        }

        $this->login($client, $admin);

        $crawler = $client->request(
            'GET',
            '/admin/redcall-users/history?form%5Bcriteria%5D=PAGER_MARKER_777&form%5BhideTechnical%5D=0&form%5Bsubmit%5D='
        );
        $this->assertResponseIsSuccessful();

        // Page 2 anchor must carry the form's criteria forward — otherwise
        // the next request sees no form payload and re-enables hideTechnical.
        $page2Links = $crawler->filter('a[href*="page=2"]');
        $this->assertGreaterThan(0, $page2Links->count(),
            'Pager must render a page 2 link when there are > 20 matches'
        );

        $href = $page2Links->first()->attr('href');
        $this->assertMatchesRegularExpression(
            '#form(?:%5B|\[)criteria(?:%5D|\])=PAGER_MARKER_777#',
            $href,
            'Page 2 URL must carry the form criteria so the filter state survives navigation'
        );
        $this->assertMatchesRegularExpression(
            '#form(?:%5B|\[)hideTechnical(?:%5D|\])=#',
            $href,
            'Page 2 URL must include hideTechnical (even empty/"0") so the unchecked state survives'
        );
    }

    public function testHistoryButtonOnAdminIndex()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin = $fixtures->createRawUser('audit_button_admin@test.com', 'password', true);
        $this->login($client, $admin);

        $crawler = $client->request('GET', '/admin/redcall-users');
        $this->assertResponseIsSuccessful();

        $link = $crawler->selectLink('Journal des actions');
        $this->assertGreaterThan(0, $link->count(), 'History log button must be on the index page');
    }
}
