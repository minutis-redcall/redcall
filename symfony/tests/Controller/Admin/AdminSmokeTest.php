<?php

namespace App\Tests\Controller\Admin;

use App\Tests\Base\BaseWebTestCase;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Smoke coverage for the Admin/* routes that don't already have a
 * dedicated test class. Per the ground rules, admin templates have
 * lower UX-ROI than user-facing pages — we cover them with happy + 403
 * (or, where the route requires a fixture this codebase doesn't expose,
 * a 404). Per-controller files in this directory cover the deeper
 * scenarios.
 */
class AdminSmokeTest extends BaseWebTestCase
{
    private function getFixtures($container) : DataFixtures
    {
        return new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_hasher')
        );
    }

    private function admin($client)
    {
        $admin = $this->getFixtures($client->getContainer())
                      ->createRawUser('admin-smoke-'.uniqid().'@test.com', 'password', true);
        $this->login($client, $admin);

        return $admin;
    }

    private function nonAdmin($client)
    {
        $user = $this->getFixtures($client->getContainer())
                     ->createRawUser('non-admin-smoke-'.uniqid().'@test.com', 'password', false);
        $this->login($client, $user);

        return $user;
    }

    private function csrf($container, string $id = 'csrf'): string
    {
        /** @var CsrfTokenManagerInterface $manager */
        $manager = $container->get('security.csrf.token_manager');
        if (!$container->get('request_stack')->getMainRequest()) {
            $req = \Symfony\Component\HttpFoundation\Request::create('/');
            $req->setSession(new \Symfony\Component\HttpFoundation\Session\Session(new \Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage()));
            $container->get('request_stack')->push($req);
        }

        return $manager->getToken($id)->getValue();
    }

    // ──────────────────────────────────────────────
    // /admin/  (HomeController)
    // ──────────────────────────────────────────────

    public function testAdminHomeOk(): void
    {
        $client = static::createClient();
        $this->admin($client);
        $client->request('GET', '/admin/');
        $this->assertResponseIsSuccessful();
    }

    public function testAdminHomeForbiddenForNonAdmin(): void
    {
        $client = static::createClient();
        $this->nonAdmin($client);
        $client->request('GET', '/admin/');
        $this->assertResponseStatusCodeSame(403);
    }

    // ──────────────────────────────────────────────
    // /admin/answer-analysis
    // ──────────────────────────────────────────────

    public function testAdminAnswerAnalysisOk(): void
    {
        $client = static::createClient();
        $this->admin($client);
        $client->request('GET', '/admin/answer-analysis');
        $this->assertResponseIsSuccessful();
    }

    public function testAdminAnswerAnalysisForbiddenForNonAdmin(): void
    {
        $client = static::createClient();
        $this->nonAdmin($client);
        $client->request('GET', '/admin/answer-analysis');
        $this->assertResponseStatusCodeSame(403);
    }

    // ──────────────────────────────────────────────
    // /admin/gdpr
    // ──────────────────────────────────────────────

    public function testAdminGdprOk(): void
    {
        $client = static::createClient();
        $this->admin($client);
        $client->request('GET', '/admin/gdpr');
        $this->assertResponseIsSuccessful();
    }

    public function testAdminGdprForbiddenForNonAdmin(): void
    {
        $client = static::createClient();
        $this->nonAdmin($client);
        $client->request('GET', '/admin/gdpr');
        $this->assertResponseStatusCodeSame(403);
    }

    // ──────────────────────────────────────────────
    // /admin/maintenance  remaining routes
    // ──────────────────────────────────────────────

    public function testMaintenanceDataSyncOk(): void
    {
        // services_test.yaml replaces GoogleTaskBundle's TaskSender with
        // Bundles\SandboxBundle\Service\NullTaskSender so the dispatched
        // tasks don't execute (and hit Google APIs without credentials).
        $client = static::createClient();
        $this->admin($client);
        $client->request('GET', '/admin/maintenance/data-sync');
        $this->assertContains($client->getResponse()->getStatusCode(), [200, 302]);
    }

    public function testMaintenanceAnnuaireNationalOk(): void
    {
        $client = static::createClient();
        $this->admin($client);
        $client->request('GET', '/admin/maintenance/annuaire-national');
        $this->assertContains($client->getResponse()->getStatusCode(), [200, 302]);
    }

    // ──────────────────────────────────────────────
    // /admin/badges/toggle-lock-{id}/{token}
    // ──────────────────────────────────────────────

    public function testBadgeToggleLockOk(): void
    {
        $client    = static::createClient();
        $container = $client->getContainer();
        $admin     = $this->admin($client);
        $fixtures  = $this->getFixtures($container);
        $badge     = $fixtures->createBadge('Lockable Badge '.uniqid(), 'TGB-LOCK-'.uniqid());

        $token = $this->csrf($container, 'token');

        $client->request('GET', sprintf('/admin/badges/toggle-lock-%d/%s', $badge->getId(), $token));
        // 200 renders the partial via Template attribute; 302 if the toggle returns redirect.
        $this->assertContains($client->getResponse()->getStatusCode(), [200, 302]);
    }

    // ──────────────────────────────────────────────
    // /admin/categories/* — remaining
    // ──────────────────────────────────────────────

    public function testCategoryToggleLockOk(): void
    {
        $client    = static::createClient();
        $container = $client->getContainer();
        $this->admin($client);
        $fixtures  = $this->getFixtures($container);
        $category  = $fixtures->createCategory('Lockable Cat '.uniqid());

        $token = $this->csrf($container, 'csrf');
        $client->request('GET', sprintf('/admin/categories/lock-unlock-%d/%s', $category->getId(), $token));
        $this->assertContains($client->getResponse()->getStatusCode(), [200, 302]);
    }

    public function testCategoryListBadgesOk(): void
    {
        $client    = static::createClient();
        $container = $client->getContainer();
        $this->admin($client);
        $category  = $this->getFixtures($container)->createCategory('Listable Cat '.uniqid());

        $client->request('GET', sprintf('/admin/categories/list-badges-in-category-%d', $category->getId()));
        $this->assertResponseIsSuccessful();
    }

    public function testCategoryRefreshOk(): void
    {
        $client    = static::createClient();
        $container = $client->getContainer();
        $this->admin($client);
        $category  = $this->getFixtures($container)->createCategory('Refreshable Cat '.uniqid());

        $client->request('GET', sprintf('/admin/categories/refresh-category-category-%d', $category->getId()));
        $this->assertResponseIsSuccessful();
    }

    // ──────────────────────────────────────────────
    // /admin/pegass — remaining
    // ──────────────────────────────────────────────

    public function testPegassRtmrOk(): void
    {
        $client = static::createClient();
        $this->admin($client);
        $client->request('GET', '/admin/pegass/rtmr');
        $this->assertResponseIsSuccessful();
    }

    public function testPegassAdministratorsOk(): void
    {
        $client = static::createClient();
        $this->admin($client);
        $client->request('GET', '/admin/pegass/administrators');
        $this->assertResponseIsSuccessful();
    }

    public function testPegassListUsersJson(): void
    {
        $client = static::createClient();
        $this->admin($client);
        $client->request('GET', '/admin/pegass/list-users');
        $this->assertResponseIsSuccessful();
    }
}
