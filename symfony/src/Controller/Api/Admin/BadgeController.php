<?php

namespace App\Controller\Api\Admin;

use App\Entity\Badge;
use App\Entity\Volunteer;
use App\Enum\Crud;
use App\Facade\Badge\BadgeFacade;
use App\Facade\Badge\BadgeFiltersFacade;
use App\Facade\Badge\BadgeReadFacade;
use App\Facade\Generic\UpdateStatusFacade;
use App\Facade\PageFilterFacade;
use App\Facade\Resource\VolunteerResourceFacade;
use App\Facade\Volunteer\VolunteerReferenceCollectionFacade;
use App\Facade\Volunteer\VolunteerReferenceFacade;
use App\Manager\BadgeManager;
use App\Manager\VolunteerManager;
use App\Transformer\BadgeTransformer;
use App\Transformer\ResourceTransformer;
use App\Transformer\VolunteerTransformer;
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
 * Badges can be skills, nominations, trainings, or whatever
 * information used to categorize volunteers.
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
     * @var VolunteerTransformer
     */
    private $volunteerTransformer;

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
        VolunteerTransformer $volunteerTransformer,
        VolunteerManager $volunteerManager,
        ResourceTransformer $resourceTransformer)
    {
        $this->badgeManager         = $badgeManager;
        $this->badgeTransformer     = $badgeTransformer;
        $this->volunteerTransformer = $volunteerTransformer;
        $this->volunteerManager     = $volunteerManager;
        $this->resourceTransformer  = $resourceTransformer;
    }

    /**
     * List all badges.
     *
     * @Endpoint(
     *   priority = 20,
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
     *   priority = 21,
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
     *   priority = 22,
     *   response = @Facade(class = BadgeReadFacade::class)
     * )
     * @Route(name="read", path="/{badgeId}", methods={"GET"})
     * @Entity("badge", expr="repository.findOneByExternalIdAndCurrentPlatform(badgeId)")
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
     *   priority = 23,
     *   request  = @Facade(class = BadgeFacade::class),
     *   response = @Facade(class = HttpNoContentFacade::class)
     * )
     * @Route(name="update", path="/{badgeId}", methods={"PUT"})
     * @Entity("badge", expr="repository.findOneByExternalIdAndCurrentPlatform(badgeId)")
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
     *   priority = 24,
     *   response = @Facade(class = HttpNoContentFacade::class)
     * )
     * @Route(name="delete", path="/{badgeId}", methods={"DELETE"})
     * @Entity("badge", expr="repository.findOneByExternalIdAndCurrentPlatform(badgeId)")
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
     *   priority = 25,
     *   request  = @Facade(class     = PageFilterFacade::class),
     *   response = @Facade(class     = QueryBuilderFacade::class,
     *                      decorates = @Facade(class = VolunteerResourceFacade::class))
     * )
     * @Route(name="volunteer_records", path="/volunteer/{badgeId}", methods={"GET"})
     * @Entity("badge", expr="repository.findOneByExternalIdAndCurrentPlatform(badgeId)")
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
     *   priority = 26,
     *   request  = @Facade(class     = VolunteerReferenceCollectionFacade::class,
     *                      decorates = @Facade(class = VolunteerReferenceFacade::class)),
     *   response = @Facade(class     = CollectionFacade::class,
     *                      decorates = @Facade(class = UpdateStatusFacade::class))
     * )
     * @Route(name="volunteer_add", path="/volunteer/{badgeId}", methods={"POST"})
     * @Entity("badge", expr="repository.findOneByExternalIdAndCurrentPlatform(badgeId)")
     * @IsGranted("BADGE", subject="badge")
     */
    public function volunteerAdd(Badge $badge, VolunteerReferenceCollectionFacade $collection) : FacadeInterface
    {
        return $this->bulkUpdateVolunteers($badge, $collection, Crud::CREATE());
    }

    /**
     * Remove a badge from a given list of volunteers.
     *
     * @Endpoint(
     *   priority = 27,
     *   request  = @Facade(class     = VolunteerReferenceCollectionFacade::class,
     *                      decorates = @Facade(class = VolunteerReferenceFacade::class)),
     *   response = @Facade(class     = CollectionFacade::class,
     *                      decorates = @Facade(class = UpdateStatusFacade::class))
     * )
     * @Route(name="volunteer_delete", path="/volunteer/{badgeId}", methods={"DELETE"})
     * @Entity("badge", expr="repository.findOneByExternalIdAndCurrentPlatform(badgeId)")
     * @IsGranted("BADGE", subject="badge")
     */
    public function volunteerDelete(Badge $badge, VolunteerReferenceCollectionFacade $collection) : FacadeInterface
    {
        return $this->bulkUpdateVolunteers($badge, $collection, Crud::DELETE());
    }

    private function bulkUpdateVolunteers(Badge $badge, VolunteerReferenceCollectionFacade $collection, Crud $action)
    {
        $response = new CollectionFacade();
        $changes  = 0;

        foreach ($collection->getEntries() as $entry) {
            /** @var VolunteerReferenceFacade $entry */
            $volunteer = $this->volunteerManager->findOneByExternalId($this->getPlatform(), $entry->getExternalId());

            if (null === $volunteer) {
                $response[] = new UpdateStatusFacade($entry->getExternalId(), false, 'Volunteer does not exist');
                continue;
            }

            if (!$this->isGranted('VOLUNTEER', $volunteer)) {
                $response[] = new UpdateStatusFacade($entry->getExternalId(), false, 'Access denied');
                continue;
            }

            if ($volunteer->isLocked()) {
                $response[] = new UpdateStatusFacade($entry->getExternalId(), false, 'Volunteer is locked');
                continue;
            }

            switch ($action) {
                case Crud::CREATE():
                    if ($volunteer->getBadges()->contains($badge)) {
                        $response[] = new UpdateStatusFacade($entry->getExternalId(), false, 'Volunteer already have that badge');
                        continue 2;
                    }

                    $volunteer->addBadge($badge);
                    $this->volunteerManager->save($volunteer);

                    break;
                case Crud::DELETE():
                    if (!$volunteer->getBadges()->contains($badge)) {
                        $response[] = new UpdateStatusFacade($entry->getExternalId(), false, 'Volunteer does not have that badge');
                        continue 2;
                    }

                    $volunteer->removeBadge($badge);
                    $this->volunteerManager->save($volunteer);

                    break;
            }

            $changes++;

            $response[] = new UpdateStatusFacade($entry->getExternalId());
        }

        if ($changes) {
            $this->badgeManager->save($badge);
        }

        return $response;
    }
}