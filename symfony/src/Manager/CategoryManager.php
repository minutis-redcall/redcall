<?php

namespace App\Manager;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\QueryBuilder;

class CategoryManager
{
    /**
     * @var CategoryRepository
     */
    private $categoryRepository;

    /**
     * @param CategoryRepository $categoryRepository
     */
    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function getSearchInCategoriesQueryBuilder(string $platform, ?string $criteria) : QueryBuilder
    {
        return $this->categoryRepository->getSearchInCategoriesQueryBuilder($platform, $criteria);
    }

    public function find(int $id) : ?Category
    {
        return $this->categoryRepository->find($id);
    }

    public function findOneByExternalIdAndCurrentPlatform(string $externalId) : ?Category
    {
        return $this->categoryRepository->findOneByExternalIdAndCurrentPlatform($externalId);
    }

    public function save(Category $category)
    {
        $this->categoryRepository->save($category);
    }

    public function remove(Category $category)
    {
        $this->categoryRepository->remove($category);
    }

    public function search(string $platform, ?string $criteria, int $limit = 0) : array
    {
        return $this->categoryRepository->search($platform, $criteria, $limit);
    }
}