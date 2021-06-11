<?php

namespace App\Controller\Api\Admin;

use App\Entity\Badge;
use App\Entity\Category;
use App\Enum\Crud;
use App\Facade\Badge\BadgeReadFacade;
use App\Facade\Badge\BadgeReferenceCollectionFacade;
use App\Facade\Badge\BadgeReferenceFacade;
use App\Facade\Category\CategoryFacade;
use App\Facade\Category\CategoryFiltersFacade;
use App\Facade\Category\CategoryReferenceCollectionFacade;
use App\Facade\Category\CategoryReferenceFacade;
use App\Facade\Generic\UpdateStatusFacade;
use App\Facade\PageFilterFacade;
use App\Manager\BadgeManager;
use App\Manager\CategoryManager;
use App\Transformer\BadgeTransformer;
use App\Transformer\CategoryTransformer;
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
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

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
     *   priority = 11,
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
     *   priority = 12,
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
     *   priority = 13,
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
            $this->getLockValidationCallback(),
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
     * @Entity("category", expr="repository.findOneByExternalIdAndCurrentPlatform(categoryId)")
     * @IsGranted("CATEGORY", subject="category")
     */
    public function delete(Category $category) : FacadeInterface
    {
        $this->validate($category, [
            $this->getLockValidationCallback(),
        ]);

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
     * @Entity("category", expr="repository.findOneByExternalIdAndCurrentPlatform(categoryId)")
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
     *   priority = 16,
     *   request  = @Facade(class     = BadgeReferenceCollectionFacade::class,
     *                      decorates = @Facade(class = BadgeReferenceFacade::class)),
     *   response = @Facade(class     = CollectionFacade::class,
     *                      decorates = @Facade(class = UpdateStatusFacade::class))
     * )
     * @Route(name="badge_add", path="/badge/{categoryId}", methods={"POST"})
     * @Entity("category", expr="repository.findOneByExternalIdAndCurrentPlatform(categoryId)")
     * @IsGranted("CATEGORY", subject="category")
     */
    public function badgeAdd(Category $category, BadgeReferenceCollectionFacade $externalIds) : FacadeInterface
    {
        return $this->bulkUpdateBadges($category, $externalIds, Crud::CREATE());
    }

    /**
     * Delete a list of badges from the given category.
     *
     * @Endpoint(
     *   priority = 17,
     *   request  = @Facade(class     = BadgeReferenceCollectionFacade::class,
     *                      decorates = @Facade(class = BadgeReferenceFacade::class)),
     *   response = @Facade(class     = CollectionFacade::class,
     *                      decorates = @Facade(class = UpdateStatusFacade::class))
     * )
     * @Route(name="badge_remove", path="/badge/{categoryId}", methods={"DELETE"})
     * @Entity("category", expr="repository.findOneByExternalIdAndCurrentPlatform(categoryId)")
     * @IsGranted("CATEGORY", subject="category")
     */
    public function badgeDelete(Category $category, BadgeReferenceCollectionFacade $externalIds) : FacadeInterface
    {
        return $this->bulkUpdateBadges($category, $externalIds, Crud::DELETE());
    }

    /**
     * Lock a list of categories.
     *
     * @Endpoint(
     *   priority = 18,
     *   request  = @Facade(class     = CategoryReferenceCollectionFacade::class,
     *                      decorates = @Facade(class = CategoryReferenceFacade::class)),
     *   response = @Facade(class     = CollectionFacade::class,
     *                      decorates = @Facade(class = UpdateStatusFacade::class))
     * )
     * @Route(name="lock", path="/bulk/lock", methods={"PUT"})
     */
    public function lock(CategoryReferenceCollectionFacade $externalIds) : FacadeInterface
    {
        return $this->bulkUpdateCategories($externalIds, Crud::LOCK());
    }

    /**
     * Unlock a list of categories.
     *
     * @Endpoint(
     *   priority = 18,
     *   request  = @Facade(class     = CategoryReferenceCollectionFacade::class,
     *                      decorates = @Facade(class = CategoryReferenceFacade::class)),
     *   response = @Facade(class     = CollectionFacade::class,
     *                      decorates = @Facade(class = UpdateStatusFacade::class))
     * )
     * @Route(name="unlock", path="/bulk/unlock", methods={"PUT"})
     */
    public function unlock(CategoryReferenceCollectionFacade $externalIds) : FacadeInterface
    {
        return $this->bulkUpdateCategories($externalIds, Crud::LOCK());
    }

    /**
     * Disable a list of categories.
     *
     * @Endpoint(
     *   priority = 19,
     *   request  = @Facade(class     = CategoryReferenceCollectionFacade::class,
     *                      decorates = @Facade(class = CategoryReferenceFacade::class)),
     *   response = @Facade(class     = CollectionFacade::class,
     *                      decorates = @Facade(class = UpdateStatusFacade::class))
     * )
     * @Route(name="disable", path="/bulk/disable", methods={"PUT"})
     */
    public function disable(CategoryReferenceCollectionFacade $externalIds) : FacadeInterface
    {
        return $this->bulkUpdateCategories($externalIds, Crud::DISABLE());
    }

    /**
     * Enable a list of categories.
     *
     * @Endpoint(
     *   priority = 20,
     *   request  = @Facade(class     = CategoryReferenceCollectionFacade::class,
     *                      decorates = @Facade(class = CategoryReferenceFacade::class)),
     *   response = @Facade(class     = CollectionFacade::class,
     *                      decorates = @Facade(class = UpdateStatusFacade::class))
     * )
     * @Route(name="enable", path="/bulk/enable", methods={"PUT"})
     */
    public function enable(CategoryReferenceCollectionFacade $externalIds) : FacadeInterface
    {
        return $this->bulkUpdateCategories($externalIds, Crud::ENABLE());
    }

    private function getLockValidationCallback() : Callback
    {
        return new Callback(function ($object, ExecutionContextInterface $context, $payload) {
            /** @var Category $object */
            if ($object->isLocked()) {
                $context->addViolation('This category is locked.');
            }
        });
    }

    private function bulkUpdateCategories(CategoryReferenceCollectionFacade $externalIds,
        Crud $action) : FacadeInterface
    {
        $response = new CollectionFacade();

        foreach ($externalIds->getEntries() as $entry) {
            /** @var CategoryReferenceFacade $entry */
            $category = $this->categoryManager->findOneByExternalIdAndCurrentPlatform($entry->getExternalId());

            if (null === $category) {
                $response[] = new UpdateStatusFacade($entry->getExternalId(), false, 'Category does not exist');
                continue;
            }

            if (!$this->isGranted('CATEGORY', $category)) {
                $response[] = new UpdateStatusFacade($entry->getExternalId(), false, 'Access denied');
                continue;
            }

            switch ($action) {
                case Crud::LOCK():
                    if ($category->isLocked()) {
                        $response[] = new UpdateStatusFacade($entry->getExternalId(), false, 'Category already locked');
                        continue 2;
                    }

                    $category->setLocked(true);
                    break;
                case Crud::UNLOCK():
                    if (!$category->isLocked()) {
                        $response[] = new UpdateStatusFacade($entry->getExternalId(), false, 'Category already unlocked');
                        continue 2;
                    }

                    $category->setLocked(false);
                    break;
                case Crud::ENABLE():
                    if ($category->isEnabled()) {
                        $response[] = new UpdateStatusFacade($entry->getExternalId(), false, 'Category already enabled');
                        continue 2;
                    }

                    $category->setEnabled(true);
                    break;
                case Crud::DISABLE():
                    if (!$category->isEnabled()) {
                        $response[] = new UpdateStatusFacade($entry->getExternalId(), false, 'Category already disabled');
                        continue 2;
                    }

                    $category->setEnabled(false);
                    break;
            }

            $this->categoryManager->save($category);

            $response[] = new UpdateStatusFacade($entry->getExternalId());
        }

        return $response;
    }

    private function bulkUpdateBadges(Category $category, BadgeReferenceCollectionFacade $externalIds, Crud $action)
    {
        $response = new CollectionFacade();
        $changes  = 0;

        foreach ($externalIds->getEntries() as $entry) {
            /** @var BadgeReferenceFacade $entry */
            $badge = $this->badgeManager->findOneByExternalId($this->getPlatform(), $entry->getExternalId());

            if (null === $badge) {
                $response[] = new UpdateStatusFacade($entry->getExternalId(), false, 'Badge does not exist');
                continue;
            }

            if (!$this->isGranted('BADGE', $badge)) {
                $response[] = new UpdateStatusFacade($entry->getExternalId(), false, 'Access denied');
                continue;
            }

            switch ($action) {
                case Crud::CREATE():
                    if ($category->getBadges()->contains($badge)) {
                        $response[] = new UpdateStatusFacade($entry->getExternalId(), false, 'Category already contains that badge');
                        continue 2;
                    }

                    $category->addBadge($badge);
                    break;
                case Crud::DELETE():
                    if (!$category->getBadges()->contains($badge)) {
                        $response[] = new UpdateStatusFacade($entry->getExternalId(), false, 'Category does not contain that badge');
                        continue 2;
                    }

                    $category->removeBadge($badge);
                    break;
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