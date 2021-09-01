<?php

namespace App\Controller\Api;

use App\Entity\Volunteer;
use App\Facade\Volunteer\VolunteerFacade;
use App\Facade\Volunteer\VolunteerFiltersFacade;
use App\Facade\Volunteer\VolunteerReadFacade;
use App\Manager\VolunteerManager;
use App\Transformer\VolunteerTransformer;
use App\Validator\Constraints\Unlocked;
use Bundles\ApiBundle\Annotation\Endpoint;
use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Base\BaseController;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Bundles\ApiBundle\Model\Facade\Http\HttpCreatedFacade;
use Bundles\ApiBundle\Model\Facade\Http\HttpNoContentFacade;
use Bundles\ApiBundle\Model\Facade\QueryBuilderFacade;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Routing\Annotation\Route;

/**
 * A volunteer is a physical person belonging to the Red Cross.
 *
 * @Route("/api/volunteer", name="api_volunteer_")
 */
class VolunteerController extends BaseController
{
    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @var VolunteerTransformer
     */
    private $volunteerTransformer;

    public function __construct(VolunteerManager $volunteerManager, VolunteerTransformer $volunteerTransformer)
    {
        $this->volunteerManager     = $volunteerManager;
        $this->volunteerTransformer = $volunteerTransformer;
    }

    /**
     * List or search among all volunteers.
     *
     * @Endpoint(
     *   priority = 500,
     *   request  = @Facade(class     = VolunteerFiltersFacade::class),
     *   response = @Facade(class     = QueryBuilderFacade::class,
     *                      decorates = @Facade(class = VolunteerReadFacade::class))
     * )
     * @Route(name="records", methods={"GET"})
     */
    public function records(VolunteerFiltersFacade $filters) : FacadeInterface
    {
        $qb = $this->volunteerManager->searchQueryBuilder($this->getPlatform(), $filters->getCriteria(), $filters->isOnlyEnabled(), $filters->isOnlyUsers());

        return new QueryBuilderFacade($qb, $filters->getPage(), function (Volunteer $volunteer) {
            return $this->volunteerTransformer->expose($volunteer);
        });
    }

    /**
     * Create a new volunteer.
     *
     * @Endpoint(
     *   priority = 505,
     *   request  = @Facade(class     = VolunteerFacade::class),
     *   response = @Facade(class     = HttpCreatedFacade::class)
     * )
     * @Route(name="create", methods={"POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function create(VolunteerFacade $facade) : FacadeInterface
    {
        $volunteer = $this->volunteerTransformer->reconstruct($facade);

        $this->validate($facade, [], ['create']);

        $this->validate($volunteer, [
            new UniqueEntity(['externalId', 'platform']),
        ]);

        $this->volunteerManager->save($volunteer);

        return new HttpCreatedFacade();
    }

    /**
     * Get a volunteer.
     *
     * @Endpoint(
     *   priority = 510,
     *   response = @Facade(class = VolunteerReadFacade::class)
     * )
     * @Route(name="read", path="/{externalId}", methods={"GET"})
     * @Entity("volunteer", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("VOLUNTEER", subject="volunteer")
     */
    public function read(Volunteer $volunteer)
    {
        return $this->volunteerTransformer->expose($volunteer);
    }

    /**
     * Update a volunteer.
     *
     * @Endpoint(
     *   priority = 515,
     *   request  = @Facade(class = VolunteerFacade::class),
     *   response = @Facade(class = HttpNoContentFacade::class)
     * )
     * @Route(name="update", path="/{externalId}", methods={"PUT"})
     * @Entity("volunteer", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("VOLUNTEER", subject="volunteer")
     * @IsGranted("ROLE_ADMIN")
     */
    public function update(Volunteer $volunteer, VolunteerFacade $facade)
    {
        $volunteer = $this->volunteerTransformer->reconstruct($facade, $volunteer);

        $this->validate($volunteer, [
            new UniqueEntity(['externalId', 'platform']),
            new Unlocked(),
        ]);

        $this->volunteerManager->save($volunteer);

        return new HttpNoContentFacade();
    }

    /**
     * Delete a volunteer.
     *
     * @Endpoint(
     *   priority = 520,
     *   response = @Facade(class = HttpNoContentFacade::class)
     * )
     * @Route(name="delete", path="/{externalId}", methods={"DELETE"})
     * @Entity("volunteer", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("VOLUNTEER", subject="volunteer")
     */
    public function delete(Volunteer $volunteer)
    {
        $this->validate($volunteer, [
            new Unlocked(),
        ]);

        $this->volunteerManager->remove($volunteer);

        return new HttpNoContentFacade();
    }
}