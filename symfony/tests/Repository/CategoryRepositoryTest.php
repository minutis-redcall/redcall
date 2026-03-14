<?php

namespace App\Tests\Repository;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CategoryRepositoryTest extends KernelTestCase
{
    /** @var CategoryRepository */
    private $repository;

    /** @var DataFixtures */
    private $fixtures;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->repository = self::$container->get('doctrine.orm.entity_manager')
            ->getRepository(Category::class);

        $this->fixtures = new DataFixtures(
            self::$container->get('doctrine.orm.entity_manager'),
            self::$container->get('security.password_encoder')
        );
    }

    // ── findOneByExternalId ──

    public function testFindOneByExternalId(): void
    {
        $category = $this->fixtures->createCategory('Cat Find', 'CAT-FIND-001');

        $found = $this->repository->findOneByExternalId('CAT-FIND-001');
        $this->assertNotNull($found);
        $this->assertSame($category->getId(), $found->getId());
    }

    public function testFindOneByExternalIdReturnsNull(): void
    {
        $this->assertNull($this->repository->findOneByExternalId('NONEXISTENT-CAT'));
    }

    // ── search ──

    public function testSearch(): void
    {
        $this->fixtures->createCategory('Searchable Category', 'CAT-SRCH-001');

        $results = $this->repository->search('Searchable Category', 100);

        $names = array_map(function (Category $c) { return $c->getName(); }, $results);
        $this->assertContains('Searchable Category', $names);
    }

    public function testSearchWithPartialMatch(): void
    {
        $this->fixtures->createCategory('Partial Match Cat', 'CAT-PART-001');

        $results = $this->repository->search('Partial', 100);

        $names = array_map(function (Category $c) { return $c->getName(); }, $results);
        $this->assertContains('Partial Match Cat', $names);
    }

    public function testSearchByExternalId(): void
    {
        $this->fixtures->createCategory('ById Cat', 'CAT-BYID-001');

        $results = $this->repository->search('CAT-BYID', 100);

        $names = array_map(function (Category $c) { return $c->getName(); }, $results);
        $this->assertContains('ById Cat', $names);
    }

    public function testSearchReturnsEmptyForNoMatch(): void
    {
        $results = $this->repository->search('XXXNONEXISTENT999', 100);
        $this->assertEmpty($results);
    }

    public function testSearchRespectsLimit(): void
    {
        $this->fixtures->createCategory('Limit Cat 1', 'CAT-LIM-001');
        $this->fixtures->createCategory('Limit Cat 2', 'CAT-LIM-002');
        $this->fixtures->createCategory('Limit Cat 3', 'CAT-LIM-003');

        $results = $this->repository->search('Limit Cat', 2);

        $this->assertLessThanOrEqual(2, count($results));
    }

    // ── getSearchInCategoriesQueryBuilder ──

    public function testGetSearchInCategoriesQueryBuilder(): void
    {
        $this->fixtures->createCategory('QB Category', 'CAT-QB-001');

        $results = $this->repository->getSearchInCategoriesQueryBuilder('QB Category')
            ->getQuery()->getResult();

        $names = array_map(function (Category $c) { return $c->getName(); }, $results);
        $this->assertContains('QB Category', $names);
    }

    public function testGetSearchInCategoriesQueryBuilderNullCriteria(): void
    {
        $this->fixtures->createCategory('All Cat', 'CAT-ALL-001');

        $results = $this->repository->getSearchInCategoriesQueryBuilder(null)
            ->getQuery()->getResult();

        $this->assertNotEmpty($results);
    }
}
