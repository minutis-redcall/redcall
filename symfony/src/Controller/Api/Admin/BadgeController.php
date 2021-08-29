<?php

namespace App\Controller\Api\Admin;

use App\Entity\Badge;
use App\Entity\Volunteer;
use App\Enum\Crud;
use App\Enum\Resource;
use App\Enum\ResourceOwnership;
use App\Facade\Badge\BadgeFacade;
use App\Facade\Badge\BadgeFiltersFacade;
use App\Facade\Badge\BadgeReadFacade;
use App\Facade\Generic\PageFilterFacade;
use App\Facade\Generic\UpdateStatusFacade;
use App\Facade\Resource\VolunteerResourceFacade;
use App\Facade\Volunteer\VolunteerReferenceCollectionFacade;
use App\Facade\Volunteer\VolunteerReferenceFacade;
use App\Manager\BadgeManager;
use App\Manager\VolunteerManager;
use App\Transformer\BadgeTransformer;
use App\Transformer\ResourceTransformer;
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
 * A badge is a skill, a nomination, a training certificate, or anything
 * that helps you filter out which people is needed in a given situation.
 *
 * During a trigger, a RedCall user can either select all the
 * volunteers, or filter out a list of people having the required
 * badges.
 *
 * @Route("/api/admin/badge", name="api_admin_badge_")
 * @IsGranted("ROLE_ADMIN")
 */
class BadgeController extends BaseController
{
    /**
     * @var BadgeManager
     */
    private $badgeManager;

    /**
     * @var BadgeTransformer
     */
    private $badgeTransformer;

    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @var ResourceTransformer
     */
    private $resourceTransformer;

    public function __construct(BadgeManager $badgeManager,
        BadgeTransformer $badgeTransformer,
        VolunteerManager $volunteerManager,
        ResourceTransformer $resourceTransformer)
    {
        $this->badgeManager        = $badgeManager;
        $this->badgeTransformer    = $badgeTransformer;
        $this->volunteerManager    = $volunteerManager;
        $this->resourceTransformer = $resourceTransformer;
    }

    /**
     * List all badges.
     *
     * @Endpoint(
     *   priority = 200,
     *   request  = @Facade(class     = BadgeFiltersFacade::class),
     *   response = @Facade(class     = QueryBuilderFacade::class,
     *                      decorates = @Facade(class = BadgeFacade::class))
     * )
     * @Route(name="records", methods={"GET"})
     */
    public function records(BadgeFiltersFacade $filters) : FacadeInterface
    {
        $qb = $this->badgeManager->getSearchInBadgesQueryBuilder(
            $this->getPlatform(),
            $filters->getCriteria(),
            true
        );

        return new QueryBuilderFacade($qb, $filters->getPage(), function (Badge $badge) {
            return $this->badgeTransformer->expose($badge);
        });
    }

    /**
     * Create a new badge.
     *
     * @Endpoint(
     *   priority = 205,
     *   request  = @Facade(class     = BadgeFacade::class),
     *   response = @Facade(class     = HttpCreatedFacade::class)
     * )
     * @Route(name="create", methods={"POST"})
     */
    public function create(BadgeFacade $facade) : FacadeInterface
    {
        $badge = $this->badgeTransformer->reconstruct($facade);

        $this->validate($badge, [
            new UniqueEntity(['platform', 'externalId']),
        ], ['create']);

        $this->badgeManager->save($badge);

        return new HttpCreatedFacade();
    }

    /**
     * Get a badge.
     *
     * @Endpoint(
     *   priority = 210,
     *   response = @Facade(class = BadgeReadFacade::class)
     * )
     * @Route(name="read", path="/{externalId}", methods={"GET"})
     * @Entity("badge", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("BADGE", subject="badge")
     */
    public function read(Badge $badge) : FacadeInterface
    {
        return $this->badgeTransformer->expose($badge);
    }

    /**
     * Update a badge.
     *
     * @Endpoint(
     *   priority = 215,
     *   request  = @Facade(class = BadgeFacade::class),
     *   response = @Facade(class = HttpNoContentFacade::class)
     * )
     * @Route(name="update", path="/{externalId}", methods={"PUT"})
     * @Entity("badge", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("BADGE", subject="badge")
     */
    public function update(Badge $badge, BadgeFacade $facade)
    {
        $badge = $this->badgeTransformer->reconstruct($facade, $badge);

        $this->validate($badge, [
            new UniqueEntity(['platform', 'externalId']),
            new Unlocked(),
        ]);

        $this->badgeManager->save($badge);

        return new HttpNoContentFacade();
    }

    /**
     * Delete a badge.
     *
     * @Endpoint(
     *   priority = 220,
     *   response = @Facade(class = HttpNoContentFacade::class)
     * )
     * @Route(name="delete", path="/{externalId}", methods={"DELETE"})
     * @Entity("badge", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("BADGE", subject="badge")
     */
    public function delete(Badge $badge)
    {
        $this->validate($badge, [
            new Unlocked(),
        ]);

        $this->badgeManager->remove($badge);

        return new HttpNoContentFacade();
    }

    /**
     * List volunteers having the given badge.
     *
     * @Endpoint(
     *   priority = 225,
     *   request  = @Facade(class     = PageFilterFacade::class),
     *   response = @Facade(class     = QueryBuilderFacade::class,
     *                      decorates = @Facade(class = VolunteerResourceFacade::class))
     * )
     * @Route(name="volunteer_records", path="/{externalId}/volunteer", methods={"GET"})
     * @Entity("badge", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("BADGE", subject="badge")
     */
    public function volunteerRecords(Badge $badge, PageFilterFacade $filters)
    {
        $qb = $this->volunteerManager->getVolunteersHavingBadgeQueryBuilder($badge);

        return new QueryBuilderFacade($qb, $filters->getPage(), function (Volunteer $volunteer) {
            return $this->resourceTransformer->expose($volunteer);
        });
    }

    /**
     * Add a badge to a given list of volunteers.
     *
     * @Endpoint(
     *   priority = 230,
     *   request  = @Facade(class     = VolunteerReferenceCollectionFacade::class,
     *                      decorates = @Facade(class = VolunteerReferenceFacade::class)),
     *   response = @Facade(class     = CollectionFacade::class,
     *                      decorates = @Facade(class = UpdateStatusFacade::class))
     * )
     * @Route(name="volunteer_add", path="/{externalId}/volunteer", methods={"POST"})
     * @Entity("badge", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("BADGE", subject="badge")
     */
    public function volunteerAdd(Badge $badge, VolunteerReferenceCollectionFacade $collection) : FacadeInterface
    {
        return $this->updateResourceCollection(
            Crud::CREATE(),
            Resource::BADGE(),
            $badge,
            Resource::VOLUNTEER(),
            $collection,
            'badge',
            ResourceOwnership::RESOLVED_RESOURCE(),
            ResourceOwnership::KNOWN_RESOURCE()
        );
    }

    /**
     * Remove a badge from a given list of volunteers.
     *
     * @Endpoint(
     *   priority = 235,
     *   request  = @Facade(class     = VolunteerReferenceCollectionFacade::class,
     *                      decorates = @Facade(class = VolunteerReferenceFacade::class)),
     *   response = @Facade(class     = CollectionFacade::class,
     *                      decorates = @Facade(class = UpdateStatusFacade::class))
     * )
     * @Route(name="volunteer_delete", path="/{externalId}/volunteer", methods={"DELETE"})
     * @Entity("badge", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("BADGE", subject="badge")
     */
    public function volunteerDelete(Badge $badge, VolunteerReferenceCollectionFacade $collection) : FacadeInterface
    {
        return $this->updateResourceCollection(
            Crud::DELETE(),
            Resource::BADGE(),
            $badge,
            Resource::VOLUNTEER(),
            $collection,
            'badge',
            ResourceOwnership::RESOLVED_RESOURCE(),
            ResourceOwnership::KNOWN_RESOURCE()
        );
    }
}