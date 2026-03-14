<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Badge;
use App\Entity\Category;
use App\Tests\Base\BaseWebTestCase;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class BadgeCategoryControllerTest extends BaseWebTestCase
{
    private function getFixtures($container) : DataFixtures
    {
        return new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_encoder')
        );
    }

    private function getCsrfToken($container, string $tokenId) : string
    {
        /** @var CsrfTokenManagerInterface $tokenManager */
        $tokenManager = $container->get('security.csrf.token_manager');

        return $tokenManager->getToken($tokenId)->getValue();
    }

    public function testBadgeIndex()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin = $fixtures->createRawUser('badge_idx_admin@test.com', 'password', true);
        $badge = $fixtures->createBadge('Test Badge Index', 'BADGE-IDX-001');

        $this->login($client, $admin);

        $crawler = $client->request('GET', '/admin/badges');

        $this->assertResponseIsSuccessful();
    }

    public function testBadgeManage()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin = $fixtures->createRawUser('badge_manage_admin@test.com', 'password', true);
        $badge = $fixtures->createBadge('Manage Badge', 'BADGE-MAN-001');

        $this->login($client, $admin);

        $crawler = $client->request('GET', sprintf('/admin/badges/manage-%d', $badge->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testBadgeToggleVisibility()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin = $fixtures->createRawUser('badge_vis_admin@test.com', 'password', true);
        $badge = $fixtures->createBadge('Visibility Badge', 'BADGE-VIS-001', true, true);

        $this->login($client, $admin);

        $token = $this->getCsrfToken($client->getContainer(), 'token');

        $client->request('GET', sprintf('/admin/badges/toggle-visibility-%d/%s', $badge->getId(), $token));

        $this->assertResponseIsSuccessful();

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();
        $updatedBadge = $em->getRepository(Badge::class)->find($badge->getId());
        $this->assertFalse((bool) $updatedBadge->getVisibility());
    }

    public function testBadgeToggleEnable()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin = $fixtures->createRawUser('badge_en_admin@test.com', 'password', true);
        $badge = $fixtures->createBadge('Enable Badge', 'BADGE-EN-001', true, true);

        $this->login($client, $admin);

        $token = $this->getCsrfToken($client->getContainer(), 'token');

        $client->request('GET', sprintf('/admin/badges/toggle-enable-%d/%s', $badge->getId(), $token));

        $this->assertResponseIsSuccessful();

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();
        $updatedBadge = $em->getRepository(Badge::class)->find($badge->getId());
        $this->assertFalse((bool) $updatedBadge->isEnabled());
    }

    public function testCategoryIndex()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin    = $fixtures->createRawUser('cat_idx_admin@test.com', 'password', true);
        $category = $fixtures->createCategory('Test Category Index', 'CAT-IDX-001');

        $this->login($client, $admin);

        $crawler = $client->request('GET', '/admin/categories/');

        $this->assertResponseIsSuccessful();
    }

    public function testCategoryForm()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin    = $fixtures->createRawUser('cat_form_admin@test.com', 'password', true);
        $category = $fixtures->createCategory('Form Category', 'CAT-FORM-001');

        $this->login($client, $admin);

        $client->request('GET', sprintf('/admin/categories/form-for-%d', $category->getId()));

        $this->assertResponseIsSuccessful();
    }

    public function testCategoryToggleEnable()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin    = $fixtures->createRawUser('cat_en_admin@test.com', 'password', true);
        $category = $fixtures->createCategory('Enable Category', 'CAT-EN-001', true);

        $this->login($client, $admin);

        $token = $this->getCsrfToken($client->getContainer(), 'token');

        $client->request('GET', sprintf('/admin/categories/enable-disable-%d/%s', $category->getId(), $token));

        $this->assertResponseIsSuccessful();

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();
        $updatedCategory = $em->getRepository(Category::class)->find($category->getId());
        $this->assertFalse((bool) $updatedCategory->isEnabled());
    }

    public function testCategoryDelete()
    {
        $client   = static::createClient();
        $fixtures = $this->getFixtures($client->getContainer());

        $admin = $fixtures->createRawUser('cat_del_admin@test.com', 'password', true);
        // Category must be disabled to be deletable
        $category = $fixtures->createCategory('Delete Category', 'CAT-DEL-001', false);

        $this->login($client, $admin);

        $token = $this->getCsrfToken($client->getContainer(), 'token');

        $client->request('GET', sprintf('/admin/categories/delete-category-%d/%s', $category->getId(), $token));

        $this->assertResponseStatusCodeSame(204);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();
        $deletedCategory = $em->getRepository(Category::class)->find($category->getId());
        $this->assertNull($deletedCategory);
    }
}
