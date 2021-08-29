<?php

namespace App\Controller\Api\Admin;

use App\Entity\Badge;
use App\Entity\Category;
use App\Enum\Crud;
use App\Enum\Resource;
use App\Enum\ResourceOwnership;
use App\Facade\Badge\BadgeReadFacade;
use App\Facade\Badge\BadgeReferenceCollectionFacade;
use App\Facade\Badge\BadgeReferenceFacade;
use App\Facade\Category\CategoryFacade;
use App\Facade\Category\CategoryFiltersFacade;
use App\Facade\Generic\PageFilterFacade;
use App\Facade\Generic\UpdateStatusFacade;
use App\Manager\BadgeManager;
use App\Manager\CategoryManager;
use App\Transformer\BadgeTransformer;
use App\Transformer\CategoryTransformer;
use App\Validator\Constraints\Unlocked;
use Bundles\ApiBundle\Annotation\Endpoint;
use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Base\BaseController;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Bundles\ApiBundle\Model\Facade\CollectionFacade;
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
     * @var BadgeManager
     */
    private $badgeManager;

    /**
     * @var CategoryTransformer
     */
    private $categoryTransformer;

    /**
     * @var BadgeTransformer
     */
    private $badgeTransformer;

    public function __construct(CategoryManager $categoryManager,
        BadgeManager $badgeManager,
        CategoryTransformer $categoryTransformer,
        BadgeTransformer $badgeTransformer)
    {
        $this->categoryManager     = $categoryManager;
        $this->badgeManager        = $badgeManager;
        $this->categoryTransformer = $categoryTransformer;
        $this->badgeTransformer    = $badgeTransformer;
    }

    /**
     * List all badge categories.
     *
     * @Endpoint(
     *   priority = 100,
     *   request  = @Facade(class     = CategoryFiltersFacade::class),
     *   response = @Facade(class     = QueryBuilderFacade::class,
     *                      decorates = @Facade(class = CategoryFacade::class))
     * )
     * @Route(name="records", methods={"GET"})
     */
    public function records(CategoryFiltersFacade $filters) : FacadeInterface
    {
        $qb = $this->categoryManager->getSearchInCategoriesQueryBuilder(
            $this->getPlatform(),
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
     *   priority = 105,
     *   request  = @Facade(class     = CategoryFacade::class),
     *   response = @Facade(class     = HttpCreatedFacade::class)
     * )
     * @Route(name="create", methods={"POST"})
     */
    public function create(CategoryFacade $facade) : FacadeInterface
    {
        $category = $this->categoryTransformer->reconstruct($facade);

        $this->validate($category, [
            new UniqueEntity(['platform', 'externalId']),
        ], ['create']);

        $this->categoryManager->save($category);

        return new HttpCreatedFacade();
    }

    /**
     * Get a badge category.
     *
     * @Endpoint(
     *   priority = 110,
     *   response = @Facade(class = CategoryFacade::class)
     * )
     * @Route(name="read", path="/{categoryId}", methods={"GET"})
     * @Entity("category", expr="repository.findOneByExternalIdAndCurrentPlatform(categoryId)")
     * @IsGranted("CATEGORY", subject="category")
     */
    public function read(Category $category) : FacadeInterface
    {
        return $this->categoryTransformer->expose($category);
    }

    /**
     * Update a badge category.
     *
     * @Endpoint(
     *   priority = 115,
     *   request  = @Facade(class = CategoryFacade::class),
     *   response = @Facade(class = HttpNoContentFacade::class)
     * )
     * @Route(name="update", path="/{categoryId}", methods={"PUT"})
     * @Entity("category", expr="repository.findOneByExternalIdAndCurrentPlatform(categoryId)")
     * @IsGranted("CATEGORY", subject="category")
     */
    public function update(Category $category, CategoryFacade $facade) : FacadeInterface
    {
        $category = $this->categoryTransformer->reconstruct($facade, $category);

        $this->validate($category, [
            new UniqueEntity(['platform', 'externalId']),
            new Unlocked(),
        ]);

        $this->categoryManager->save($category);

        return new HttpNoContentFacade();
    }

    /**
     * Delete a badge category.
     *
     * @Endpoint(
     *   priority = 120,
     *   response = @Facade(class = HttpNoContentFacade::class)
     * )
     * @Route(name="delete", path="/{categoryId}", methods={"DELETE"})
     * @Entity("category", expr="repository.findOneByExternalIdAndCurrentPlatform(categoryId)")
     * @IsGranted("CATEGORY", subject="category")
     */
    public function delete(Category $category) : FacadeInterface
    {
        $this->validate($category, [
            new Unlocked(),
        ]);

        $this->categoryManager->remove($category);

        return new HttpNoContentFacade();
    }

    /**
     * List badges in a given category.
     *
     * @Endpoint(
     *   priority = 125,
     *   request  = @Facade(class     = PageFilterFacade::class),
     *   response = @Facade(class     = QueryBuilderFacade::class,
     *                      decorates = @Facade(class = BadgeReadFacade::class))
     * )
     * @Route(name="badge_records", path="/{externalId}/badge", methods={"GET"})
     * @Entity("category", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("CATEGORY", subject="category")
     */
    public function badgeRecords(Category $category, PageFilterFacade $page) : FacadeInterface
    {
        $qb = $this->badgeManager->getBadgesInCategoryQueryBuilder($this->getPlatform(), $category);

        return new QueryBuilderFacade($qb, $page->getPage(), function (Badge $badge) {
            return $this->badgeTransformer->expose($badge);
        });
    }

    /**
     * Add a list of badges in the given category.
     *
     * @Endpoint(
     *   priority = 130,
     *   request  = @Facade(class     = BadgeReferenceCollectionFacade::class,
     *                      decorates = @Facade(class = BadgeReferenceFacade::class)),
     *   response = @Facade(class     = CollectionFacade::class,
     *                      decorates = @Facade(class = UpdateStatusFacade::class))
     * )
     * @Route(name="badge_add", path="/{externalId}/badge", methods={"POST"})
     * @Entity("category", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("CATEGORY", subject="category")
     */
    public function badgeAdd(Category $category, BadgeReferenceCollectionFacade $collection) : FacadeInterface
    {
        return $this->updateResourceCollection(
            Crud::CREATE(),
            Resource::CATEGORY(),
            $category,
            Resource::BADGE(),
            $collection,
            'badge',
            ResourceOwnership::KNOWN_RESOURCE(),
            ResourceOwnership::RESOLVED_RESOURCE()
        );
    }

    /**
     * Delete a list of badges from the given category.
     *
     * @Endpoint(
     *   priority = 135,
     *   request  = @Facade(class     = BadgeReferenceCollectionFacade::class,
     *                      decorates = @Facade(class = BadgeReferenceFacade::class)),
     *   response = @Facade(class     = CollectionFacade::class,
     *                      decorates = @Facade(class = UpdateStatusFacade::class))
     * )
     * @Route(name="badge_remove", path="/{externalId}/badge", methods={"DELETE"})
     * @Entity("category", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("CATEGORY", subject="category")
     */
    public function badgeDelete(Category $category, BadgeReferenceCollectionFacade $collection) : FacadeInterface
    {
        return $this->updateResourceCollection(
            Crud::DELETE(),
            Resource::CATEGORY(),
            $category,
            Resource::BADGE(),
            $collection,
            'badge',
            ResourceOwnership::KNOWN_RESOURCE(),
            ResourceOwnership::RESOLVED_RESOURCE()
        );
    }
}