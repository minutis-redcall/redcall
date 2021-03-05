<?php

namespace App\Controller\Api\Admin;

use App\Entity\Badge;
use App\Entity\Category;
use App\Facade\Admin\Category\CategoryFacade;
use App\Facade\Admin\Category\CategoryFiltersFacade;
use App\Manager\CategoryManager;
use App\Transformer\Admin\CategoryTransformer;
use Bundles\ApiBundle\Annotation\Endpoint;
use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Base\BaseController;
use Bundles\ApiBundle\Model\Facade\Http\HttpCreatedFacade;
use Bundles\ApiBundle\Model\Facade\Http\HttpNoContentFacade;
use Bundles\ApiBundle\Model\Facade\QueryBuilderFacade;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Badges can be grouped in categories, so they are rendered
 * all together instead of being mixed with others in the
 * "create trigger" form.
 *
 * They are organized by category priority first, then badges
 * in the category are ordered by their priority, finally we
 * render non-categorized badges.
 *
 * @Route("/api/admin/category", name="api_admin_category_")
 * @IsGranted("ROLE_ADMIN")
 */
class CategoryController extends BaseController
{
    /**
     * @var CategoryManager
     */
    private $categoryManager;

    /**
     * @var CategoryTransformer
     */
    private $categoryTransformer;

    public function __construct(CategoryManager $categoryManager, CategoryTransformer $categoryTransformer)
    {
        $this->categoryManager     = $categoryManager;
        $this->categoryTransformer = $categoryTransformer;
    }

    /**
     * List all badge categories.
     *
     * @Endpoint(
     *   priority = 10,
     *   request  = @Facade(class     = CategoryFiltersFacade::class),
     *   response = @Facade(class     = QueryBuilderFacade::class,
     *                      decorates = @Facade(class = CategoryFacade::class))
     * )
     * @Route(name="records", methods={"GET"})
     */
    public function records(CategoryFiltersFacade $filters)
    {
        $qb = $this->categoryManager->getSearchInCategoriesQueryBuilder(
            $filters->getCriteria()
        );

        return new QueryBuilderFacade($qb, $filters->getPage(), function (Category $category) {
            return $this->categoryTransformer->expose($category);
        });
    }

    /**
     * Create a new badge category.
     *
     * @Endpoint(
     *   priority = 11,
     *   request  = @Facade(class     = CategoryFacade::class),
     *   response = @Facade(class     = HttpCreatedFacade::class)
     * )
     * @Route(name="create", methods={"POST"})
     */
    public function create(CategoryFacade $facade)
    {
        $category = $this->categoryTransformer->reconstruct($facade);

        $this->validate($category, [
            new UniqueEntity('externalId'),
        ]);

        $this->categoryManager->save($category);

        return new HttpCreatedFacade();
    }

    /**
     * Get a badge category.
     *
     * @Endpoint(
     *   priority = 12,
     *   response = @Facade(class = CategoryFacade::class)
     * )
     * @Route(name="read", path="/{categoryId}", methods={"GET"})
     * @Entity("category", expr="repository.find(categoryId)")
     * @IsGranted("CATEGORY", subject="category")
     */
    public function read(Category $category)
    {
        return $this->categoryTransformer->expose($category);
    }

    /**
     * Update a badge category.
     *
     * @Endpoint(
     *   priority = 13,
     *   request  = @Facade(class = CategoryFacade::class),
     *   response = @Facade(class = HttpNoContentFacade::class)
     * )
     * @Route(name="update", path="/{categoryId}", methods={"PUT"})
     * @Entity("category", expr="repository.find(categoryId)")
     * @IsGranted("CATEGORY", subject="category")
     */
    public function update(Category $category, CategoryFacade $facade)
    {
        $category = $this->categoryTransformer->reconstruct($facade, $category);

        $this->validate($category, [
            new UniqueEntity('externalId'),
        ]);

        $this->categoryManager->save($category);

        return new HttpNoContentFacade();
    }

    /**
     * Delete a badge category.
     *
     * @Endpoint(
     *   priority = 14,
     *   response = @Facade(class = HttpNoContentFacade::class)
     * )
     * @Route(name="delete", path="/{categoryId}", methods={"DELETE"})
     * @Entity("category", expr="repository.find(categoryId)")
     * @IsGranted("CATEGORY", subject="category")
     */
    public function delete(Category $category)
    {
        $this->categoryManager->remove($category);

        return new HttpNoContentFacade();
    }


    public function badgeRecords(Category $category)
    {

    }

    public function badgeAdd(Category $category, Badge $badge)
    {

    }

    public function badgeRemove(Category $category, Badge $badge)
    {

    }
}