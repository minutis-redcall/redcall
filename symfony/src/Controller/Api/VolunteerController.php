<?php

namespace App\Controller\Api;

use App\Entity\Volunteer;
use App\Facade\Volunteer\VolunteerFiltersFacade;
use App\Facade\Volunteer\VolunteerReadFacade;
use App\Manager\VolunteerManager;
use App\Transformer\VolunteerTransformer;
use Bundles\ApiBundle\Annotation\Endpoint;
use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Base\BaseController;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Bundles\ApiBundle\Model\Facade\QueryBuilderFacade;
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

}