<?php

namespace App\Controller\Api\Admin;

use App\Entity\Category;
use App\Facade\Admin\Category\CategoryFacade;
use App\Facade\Admin\Category\CategoryFiltersFacade;
use App\Manager\CategoryManager;
use App\Transformer\Admin\CategoryTransformer;
use Bundles\ApiBundle\Annotation\Endpoint;
use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Model\Facade\QueryBuilderFacade;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
class CategoryController extends AbstractController
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

    public function create()
    {

    }

    public function read()
    {

    }

    public function update(Category $category)
    {

    }

    public function delete(Category $category)
    {

    }

    public function badgeRecords(Category $category)
    {

    }

    public function badgeAdd(Category $category)
    {

    }

    public function badgeRemove(Category $category)
    {

    }
}