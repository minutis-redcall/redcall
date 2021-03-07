<?php

namespace App\Controller\Api\Admin;

use App\Entity\Badge;
use App\Entity\Category;
use App\Enum\Crud;
use App\Facade\Admin\Badge\BadgeReadFacade;
use App\Facade\Admin\Badge\BadgeReferenceCollectionFacade;
use App\Facade\Admin\Badge\BadgeReferenceFacade;
use App\Facade\Admin\Category\CategoryFacade;
use App\Facade\Admin\Category\CategoryFiltersFacade;
use App\Facade\Generic\UpdateStatusFacade;
use App\Facade\PageFilterFacade;
use App\Manager\BadgeManager;
use App\Manager\CategoryManager;
use App\Transformer\Admin\BadgeTransformer;
use App\Transformer\Admin\CategoryTransformer;
use Bundles\ApiBundle\Annotation\Endpoint;
use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Base\BaseController;
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
     * @Entity("category", expr="repository.findOneByExternalId(categoryId)")
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
     * @Entity("category", expr="repository.findOneByExternalId(categoryId)")
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
     * @Entity("category", expr="repository.findOneByExternalId(categoryId)")
     * @IsGranted("CATEGORY", subject="category")
     */
    public function delete(Category $category)
    {
        $this->categoryManager->remove($category);

        return new HttpNoContentFacade();
    }

    /**
     * List badges in a given category.
     *
     * @Endpoint(
     *   priority = 15,
     *   request  = @Facade(class     = PageFilterFacade::class),
     *   response = @Facade(class     = QueryBuilderFacade::class,
     *                      decorates = @Facade(class = BadgeReadFacade::class))
     * )
     * @Route(name="badge_records", path="/badge/{categoryId}", methods={"GET"})
     * @Entity("category", expr="repository.findOneByExternalId(categoryId)")
     * @IsGranted("CATEGORY", subject="category")
     */
    public function badgeRecords(Category $category, PageFilterFacade $page)
    {
        $qb = $this->badgeManager->getBadgesInCategoryQueryBuilder($category);

        return new QueryBuilderFacade($qb, $page->getPage(), function (Badge $badge) {
            return $this->badgeTransformer->expose($badge);
        });
    }

    /**
     * Add a list of badges in the given category.
     *
     * @Endpoint(
     *   priority = 16,
     *   request  = @Facade(class     = BadgeReferenceCollectionFacade::class,
     *                      decorates = @Facade(class = BadgeReferenceFacade::class)),
     *   response = @Facade(class     = CollectionFacade::class,
     *                      decorates = @Facade(class = UpdateStatusFacade::class))
     * )
     * @Route(name="badge_add", path="/badge/{categoryId}", methods={"POST"})
     * @Entity("category", expr="repository.findOneByExternalId(categoryId)")
     * @IsGranted("CATEGORY", subject="category")
     */
    public function badgeAdd(Category $category, BadgeReferenceCollectionFacade $externalIds)
    {
        return $this->badgeAddOrRemove($category, $externalIds, Crud::CREATE());
    }

    /**
     * Remove a list of badges from the given category.
     *
     * @Endpoint(
     *   priority = 17,
     *   request  = @Facade(class     = BadgeReferenceCollectionFacade::class,
     *                      decorates = @Facade(class = BadgeReferenceFacade::class)),
     *   response = @Facade(class     = CollectionFacade::class,
     *                      decorates = @Facade(class = UpdateStatusFacade::class))
     * )
     * @Route(name="badge_remove", path="/badge/{categoryId}", methods={"DELETE"})
     * @Entity("category", expr="repository.findOneByExternalId(categoryId)")
     * @IsGranted("CATEGORY", subject="category")
     */
    public function badgeRemove(Category $category, BadgeReferenceCollectionFacade $externalIds)
    {
        return $this->badgeAddOrRemove($category, $externalIds, Crud::DELETE());
    }

    private function badgeAddOrRemove(Category $category, BadgeReferenceCollectionFacade $externalIds, Crud $action)
    {
        $response = new CollectionFacade();
        $changes  = 0;

        foreach ($externalIds->getEntries() as $entry) {
            /** @var BadgeReferenceFacade $entry */
            $badge = $this->badgeManager->findOneByExternalId($entry->getExternalId());

            if (null === $badge) {
                return new UpdateStatusFacade($entry->getExternalId(), false, 'Badge does not exist');
            }

            if (!$this->isGranted('BADGE', $badge)) {
                return new UpdateStatusFacade($entry->getExternalId(), false, 'Access denied');
            }

            if ($category->getBadges()->contains($badge)) {
                $response[] = new UpdateStatusFacade($entry->getExternalId(), false, 'Category already contains that badge');
                continue;
            }

            if (Crud::CREATE()->equals($action)) {
                $category->addBadge($badge);
            } else {
                $category->removeBadge($badge);
            }

            $changes++;

            $response[] = new UpdateStatusFacade($entry->getExternalId());
        }

        if ($changes) {
            $this->categoryManager->save($category);
        }

        return $response;
    }
}