<?php

namespace App\Controller\Api\Admin;

use App\Entity\User;
use App\Facade\User\UserFacade;
use App\Facade\User\UserFiltersFacade;
use App\Facade\User\UserReadFacade;
use App\Manager\UserManager;
use App\Transformer\UserTransformer;
use Bundles\ApiBundle\Annotation\Endpoint;
use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Base\BaseController;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Bundles\ApiBundle\Model\Facade\Http\HttpCreatedFacade;
use Bundles\ApiBundle\Model\Facade\QueryBuilderFacade;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Users are the ones using the RedCall application to trigger
 * volunteers.
 *
 * They are associated with a list of structures they can trigger
 *
 * @Route("/api/admin/user", name="api_admin_user_")
 * @IsGranted("ROLE_ADMIN")
 */
class UserController extends BaseController
{
    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var UserTransformer
     */
    private $userTransformer;

    public function __construct(UserManager $userManager, UserTransformer $userTransformer)
    {
        $this->userManager     = $userManager;
        $this->userTransformer = $userTransformer;
    }

    /**
     * List all users.
     *
     * @Endpoint(
     *   priority = 200,
     *   request  = @Facade(class     = UserFiltersFacade::class),
     *   response = @Facade(class     = QueryBuilderFacade::class,
     *                      decorates = @Facade(class = UserReadFacade::class))
     * )
     * @Route(name="records", methods={"GET"})
     */
    public function records(UserFiltersFacade $filters) : FacadeInterface
    {
        $qb = $this->userManager->searchQueryBuilder(
            $filters->getCriteria(),
            $filters->isOnlyAdmins(),
            $filters->isOnlyDevelopers()
        );

        return new QueryBuilderFacade($qb, $filters->getPage(), function (User $user) {
            return $this->userTransformer->expose($user);
        });
    }

    /**
     * Create a new user.
     *
     * @Endpoint(
     *   priority = 205,
     *   request  = @Facade(class     = UserFacade::class),
     *   response = @Facade(class     = HttpCreatedFacade::class)
     * )
     * @Route(name="create", methods={"POST"})
     */
    public function create(UserFacade $facade) : FacadeInterface
    {
        $user = $this->userTransformer->reconstruct($facade);

        $this->validate($user, [
            new UniqueEntity(['username']),
            $this->getRootValidationCallback(),
        ]);

        $this->userManager->save($user);

        return new HttpCreatedFacade();
    }

    private function getMeValidationCallback() : Callback
    {
        return new Callback(function ($object, ExecutionContextInterface $context) {
            /** @var User $object */
            if ($this->getSecurity()->getUser()->equalsTo($object)) {
                $context->addViolation('Users are not allowed to update themselves.');
            }
        });
    }

    private function getRootValidationCallback() : Callback
    {
        return new Callback(function ($object, ExecutionContextInterface $context) {
            /** @var User $object */
            if (!$this->getSecurity()->getUser()->isRoot() && $object->isRoot()) {
                $context->addViolation('Only root users can set other users as root');
            }
        });
    }

}