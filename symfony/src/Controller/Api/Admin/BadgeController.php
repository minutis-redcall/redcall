<?php

namespace App\Controller\Api\Admin;

use App\Entity\Badge;
use App\Facade\Admin\Badge\BadgeFacade;
use App\Facade\Admin\Badge\BadgeFiltersFacade;
use App\Manager\BadgeManager;
use App\Transformer\Admin\BadgeTransformer;
use Bundles\ApiBundle\Annotation\Endpoint;
use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Base\BaseController;
use Bundles\ApiBundle\Model\Facade\Http\HttpCreatedFacade;
use Bundles\ApiBundle\Model\Facade\QueryBuilderFacade;
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

    public function __construct(BadgeManager $badgeManager, BadgeTransformer $badgeTransformer)
    {
        $this->badgeManager     = $badgeManager;
        $this->badgeTransformer = $badgeTransformer;
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
    public function records(BadgeFiltersFacade $filters)
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
     * Create a new badge category.
     *
     * @Endpoint(
     *   priority = 21,
     *   request  = @Facade(class     = BadgeFacade::class),
     *   response = @Facade(class     = HttpCreatedFacade::class)
     * )
     * @Route(name="create", methods={"POST"})
     */
    public function create(BadgeFacade $facade)
    {
        $badge = $this->badgeTransformer->reconstruct($facade);

        $this->validate($badge, [
            new UniqueEntity('externalId'),
        ]);

        $this->badgeManager->save($badge);

        return new HttpCreatedFacade();
    }

    public function read()
    {

    }

    public function update()
    {

    }

    public function delete()
    {

    }

    public function volunteerRecords()
    {

    }

    public function volunteerAdd()
    {

    }

    public function volunteerDelete()
    {

    }

    public function lock()
    {

    }

    public function unlock()
    {

    }

    public function enable()
    {

    }

    public function disable()
    {

    }
}