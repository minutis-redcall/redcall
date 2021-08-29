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
use App\Facade\Badge\BadgeReferenceCollectionFacade;
use App\Facade\Badge\BadgeReferenceFacade;
use App\Facade\Generic\PageFilterFacade;
use App\Facade\Generic\UpdateStatusFacade;
use App\Facade\Resource\BadgeResourceFacade;
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

    /**
     * List badges covered by the given badge.
     *
     * @Endpoint(
     *   priority = 240,
     *   response = @Facade(class     = CollectionFacade::class,
     *                      decorates = @Facade(class = BadgeResourceFacade::class))
     * )
     * @Route(name="coverage_records", path="/{externalId}/coverage", methods={"GET"})
     * @Entity("badge", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("BADGE", subject="badge")
     */
    public function coverageRecords(Badge $badge)
    {
        $facade = new CollectionFacade();

        foreach ($badge->getCoveredBadges() as $covered) {
            $facade[] = $this->resourceTransformer->expose($covered);
        }

        return $facade;
    }

    /**
     * Make the given badge cover one or several others
     *
     * @Endpoint(
     *   priority = 245,
     *   request  = @Facade(class     = BadgeReferenceCollectionFacade::class,
     *                      decorates = @Facade(class = BadgeReferenceFacade::class)),
     *   response = @Facade(class     = CollectionFacade::class,
     *                      decorates = @Facade(class = UpdateStatusFacade::class))
     * )
     * @Route(name="coverage_add", path="/{externalId}/coverage", methods={"POST"})
     * @Entity("badge", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("BADGE", subject="badge")
     */
    public function coverageAdd(Badge $badge, BadgeReferenceCollectionFacade $collection) : FacadeInterface
    {
        return $this->updateResourceCollection(
            Crud::CREATE(),
            Resource::BADGE(),
            $badge,
            Resource::BADGE(),
            $collection,
            'children',
            ResourceOwnership::KNOWN_RESOURCE(),
            ResourceOwnership::RESOLVED_RESOURCE()
        );
    }

    /**
     * Do not put the given badge above one or several others anymore
     *
     * @Endpoint(
     *   priority = 250,
     *   request  = @Facade(class     = BadgeReferenceCollectionFacade::class,
     *                      decorates = @Facade(class = BadgeReferenceFacade::class)),
     *   response = @Facade(class     = CollectionFacade::class,
     *                      decorates = @Facade(class = UpdateStatusFacade::class))
     * )
     * @Route(name="coverage_remove", path="/{externalId}/coverage", methods={"DELETE"})
     * @Entity("badge", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("BADGE", subject="badge")
     */
    public function coverageRemove(Badge $badge, BadgeReferenceCollectionFacade $collection) : FacadeInterface
    {
        return $this->updateResourceCollection(
            Crud::DELETE(),
            Resource::BADGE(),
            $badge,
            Resource::BADGE(),
            $collection,
            'children',
            ResourceOwnership::KNOWN_RESOURCE(),
            ResourceOwnership::RESOLVED_RESOURCE()
        );
    }

    /**
     * List badges replaced by the given badge (synonyms).
     *
     * @Endpoint(
     *   priority = 255,
     *   response = @Facade(class     = CollectionFacade::class,
     *                      decorates = @Facade(class = BadgeResourceFacade::class))
     * )
     * @Route(name="replacement_records", path="/{externalId}/replacement", methods={"GET"})
     * @Entity("badge", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("BADGE", subject="badge")
     */
    public function replacementRecords(Badge $badge)
    {
        $facade = new CollectionFacade();

        foreach ($badge->getSynonyms() as $covered) {
            $facade[] = $this->resourceTransformer->expose($covered);
        }

        return $facade;
    }

    /**
     * Replace one or several badges by the given one
     *
     * @Endpoint(
     *   priority = 260,
     *   request  = @Facade(class     = BadgeReferenceCollectionFacade::class,
     *                      decorates = @Facade(class = BadgeReferenceFacade::class)),
     *   response = @Facade(class     = CollectionFacade::class,
     *                      decorates = @Facade(class = UpdateStatusFacade::class))
     * )
     * @Route(name="replacement_add", path="/{externalId}/replacement", methods={"POST"})
     * @Entity("badge", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("BADGE", subject="badge")
     */
    public function replacementAdd(Badge $badge, BadgeReferenceCollectionFacade $collection) : FacadeInterface
    {
        return $this->updateResourceCollection(
            Crud::CREATE(),
            Resource::BADGE(),
            $badge,
            Resource::BADGE(),
            $collection,
            'synonym',
            ResourceOwnership::KNOWN_RESOURCE(),
            ResourceOwnership::RESOLVED_RESOURCE()
        );
    }

    /**
     * Unmark one or several badges from being synonyms of the given one
     *
     * @Endpoint(
     *   priority = 265,
     *   request  = @Facade(class     = BadgeReferenceCollectionFacade::class,
     *                      decorates = @Facade(class = BadgeReferenceFacade::class)),
     *   response = @Facade(class     = CollectionFacade::class,
     *                      decorates = @Facade(class = UpdateStatusFacade::class))
     * )
     * @Route(name="replacement_remove", path="/{externalId}/replacement", methods={"DELETE"})
     * @Entity("badge", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("BADGE", subject="badge")
     */
    public function replacementRemove(Badge $badge, BadgeReferenceCollectionFacade $collection) : FacadeInterface
    {
        return $this->updateResourceCollection(
            Crud::DELETE(),
            Resource::BADGE(),
            $badge,
            Resource::BADGE(),
            $collection,
            'synonym',
            ResourceOwnership::KNOWN_RESOURCE(),
            ResourceOwnership::RESOLVED_RESOURCE()
        );
    }

    /**
     * Lock a badge.
     *
     * @Endpoint(
     *   priority = 270,
     *   response = @Facade(class = HttpNoContentFacade::class)
     * )
     * @Route(name="lock", path="/lock/{externalId}", methods={"PUT"})
     * @Entity("badge", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("BADGE", subject="badge")
     */
    public function lock(Badge $badge)
    {
        $badge->setLocked(true);

        $this->badgeManager->save($badge);

        return new HttpNoContentFacade();
    }

    /**
     * Unlock a badge.
     *
     * @Endpoint(
     *   priority = 275,
     *   response = @Facade(class = HttpNoContentFacade::class)
     * )
     * @Route(name="unlock", path="/unlock/{externalId}", methods={"PUT"})
     * @Entity("badge", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("BADGE", subject="badge")
     */
    public function unlock(Badge $badge)
    {
        $badge->setLocked(false);

        $this->badgeManager->save($badge);

        return new HttpNoContentFacade();
    }

    /**
     * Disable a badge.
     *
     * @Endpoint(
     *   priority = 280,
     *   response = @Facade(class = HttpNoContentFacade::class)
     * )
     * @Route(name="disable", path="/disable/{externalId}", methods={"PUT"})
     * @Entity("badge", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("BADGE", subject="badge")
     */
    public function disable(Badge $badge)
    {
        $this->validate($badge, [
            new Unlocked(),
        ]);

        $badge->setEnabled(false);

        $this->badgeManager->save($badge);

        return new HttpNoContentFacade();
    }

    /**
     * Enable a badge.
     *
     * @Endpoint(
     *   priority = 285,
     *   response = @Facade(class = HttpNoContentFacade::class)
     * )
     * @Route(name="enable", path="/enable/{externalId}", methods={"PUT"})
     * @Entity("badge", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("BADGE", subject="badge")
     */
    public function enable(Badge $badge)
    {
        $this->validate($badge, [
            new Unlocked(),
        ]);

        $badge->setEnabled(true);

        $this->badgeManager->save($badge);

        return new HttpNoContentFacade();
    }
}