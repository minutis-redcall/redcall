<?php

namespace App\Controller\Api\Admin;

use App\Entity\Structure;
use App\Entity\User;
use App\Enum\Crud;
use App\Enum\Resource;
use App\Enum\ResourceOwnership;
use App\Facade\Generic\PageFilterFacade;
use App\Facade\Generic\UpdateStatusFacade;
use App\Facade\Resource\StructureResourceFacade;
use App\Facade\Structure\StructureReferenceCollectionFacade;
use App\Facade\Structure\StructureReferenceFacade;
use App\Facade\User\UserFacade;
use App\Facade\User\UserFiltersFacade;
use App\Facade\User\UserReadFacade;
use App\Manager\UserManager;
use App\Transformer\ResourceTransformer;
use App\Transformer\UserTransformer;
use App\Validator\Constraints\Unlocked;
use Bundles\ApiBundle\Annotation\Endpoint;
use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Base\BaseController;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Bundles\ApiBundle\Model\Facade\CollectionFacade;
use Bundles\ApiBundle\Model\Facade\Http\HttpCreatedFacade;
use Bundles\ApiBundle\Model\Facade\Http\HttpNoContentFacade;
use Bundles\ApiBundle\Model\Facade\QueryBuilderFacade;
use Bundles\PasswordLoginBundle\Manager\PasswordRecoveryManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Users are people using the RedCall application to trigger volunteers.
 *
 * Most of the users are associated with a volunteer and list of structures
 * they can trigger, but users can also be developers (to access the APIs and
 * synchronize their data source with RedCall), administrators (who supervise
 * and support everyone using the application) and platform managers or root
 * (who manage resources across supported countries).
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

    /**
     * @var ResourceTransformer
     */
    private $resourceTransformer;

    /**
     * @var PasswordRecoveryManager
     */
    private $passwordRecoveryManager;

    public function __construct(UserManager $userManager,
        UserTransformer $userTransformer,
        ResourceTransformer $resourceTransformer,
        PasswordRecoveryManager $passwordRecoveryManager)
    {
        $this->userManager             = $userManager;
        $this->userTransformer         = $userTransformer;
        $this->resourceTransformer     = $resourceTransformer;
        $this->passwordRecoveryManager = $passwordRecoveryManager;
    }

    /**
     * List all users.
     *
     * @Endpoint(
     *   priority = 300,
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
     *   priority = 305,
     *   request  = @Facade(class     = UserFacade::class),
     *   response = @Facade(class     = HttpCreatedFacade::class)
     * )
     * @Route(name="create", methods={"POST"})
     */
    public function create(UserFacade $facade) : FacadeInterface
    {
        $user = $this->userTransformer->reconstruct($facade);

        $this->validate($facade, [], ['create']);

        $this->validate($user, [
            new UniqueEntity(['username']),
            $this->getRootValidationCallback(),
        ]);

        $this->userManager->save($user);

        return new HttpCreatedFacade();
    }

    /**
     * Get a user.
     *
     * @Endpoint(
     *   priority = 310,
     *   response = @Facade(class = UserReadFacade::class)
     * )
     * @Route(name="read", path="/{email}", methods={"GET"})
     * @Entity("user", expr="repository.findByUsernameAndCurrentPlatform(email)")
     * @IsGranted("USER", subject="user")
     */
    public function read(User $user)
    {
        return $this->userTransformer->expose($user);
    }

    /**
     * Update a user.
     *
     * @Endpoint(
     *   priority = 315,
     *   request  = @Facade(class = UserFacade::class),
     *   response = @Facade(class = HttpNoContentFacade::class)
     * )
     * @Route(name="update", path="/{email}", methods={"PUT"})
     * @Entity("user", expr="repository.findByUsernameAndCurrentPlatform(email)")
     * @IsGranted("USER", subject="user")
     */
    public function update(User $user, UserFacade $facade)
    {
        $olderExternalId = $user->getExternalId();

        $user = $this->userTransformer->reconstruct($facade, $user);

        $this->validate($user, [
            new UniqueEntity(['username']),
            $this->getMeValidationCallback(),
            $this->getRootValidationCallback(),
        ]);

        $this->userManager->save($user);

        if ($olderExternalId !== $user->getExternalId()) {
            $this->userManager->changeVolunteer($user, $this->getPlatform(), $user->getExternalId());
        }

        return new HttpNoContentFacade();
    }

    /**
     * Delete a user.
     *
     * @Endpoint(
     *   priority = 320,
     *   response = @Facade(class = HttpNoContentFacade::class)
     * )
     * @Route(name="delete", path="/{email}", methods={"DELETE"})
     * @Entity("user", expr="repository.findByUsernameAndCurrentPlatform(email)")
     * @IsGranted("USER", subject="user")
     */
    public function delete(User $user)
    {
        $this->validate($user, [
            new Unlocked(),
        ]);

        $this->userManager->remove($user);

        return new HttpNoContentFacade();
    }

    /**
     * List structures that a user is responsible for / can trigger.
     *
     * @Endpoint(
     *   priority = 325,
     *   request  = @Facade(class     = PageFilterFacade::class),
     *   response = @Facade(class     = QueryBuilderFacade::class,
     *                      decorates = @Facade(class = StructureResourceFacade::class))
     * )
     * @Route(name="structure_records", path="/{email}/structure", methods={"GET"})
     * @Entity("user", expr="repository.findByUsernameAndCurrentPlatform(email)")
     * @IsGranted("USER", subject="user")
     */
    public function structureRecords(User $user, PageFilterFacade $filters)
    {
        $qb = $this->userManager->getUserStructuresQueryBuilder($this->getPlatform(), $user);

        return new QueryBuilderFacade($qb, $filters->getPage(), function (Structure $structure) {
            return $this->resourceTransformer->expose($structure);
        });
    }

    /**
     * Grant one or several structures to the user.
     *
     * @Endpoint(
     *   priority = 330,
     *   request  = @Facade(class     = StructureReferenceCollectionFacade::class,
     *                      decorates = @Facade(class = StructureReferenceFacade::class)),
     *   response = @Facade(class     = CollectionFacade::class,
     *                      decorates = @Facade(class = UpdateStatusFacade::class))
     * )
     * @Route(name="structure_add", path="/{email}/structure", methods={"POST"})
     * @Entity("user", expr="repository.findByUsernameAndCurrentPlatform(email)")
     * @IsGranted("USER", subject="user")
     */
    public function structureAdd(User $user, StructureReferenceCollectionFacade $collection) : FacadeInterface
    {
        $this->validate($user, [
            new Unlocked(),
        ]);

        return $this->updateResourceCollection(
            Crud::CREATE(),
            Resource::USER(),
            $user,
            Resource::STRUCTURE(),
            $collection,
            'structure',
            ResourceOwnership::KNOWN_RESOURCE(),
            ResourceOwnership::KNOWN_RESOURCE()
        );
    }

    /**
     * Remove one or several structures from user's scope.
     *
     * @Endpoint(
     *   priority = 335,
     *   request  = @Facade(class     = StructureReferenceCollectionFacade::class,
     *                      decorates = @Facade(class = StructureReferenceFacade::class)),
     *   response = @Facade(class     = CollectionFacade::class,
     *                      decorates = @Facade(class = UpdateStatusFacade::class))
     * )
     * @Route(name="structure_remove", path="/{email}/structure", methods={"DELETE"})
     * @Entity("user", expr="repository.findByUsernameAndCurrentPlatform(email)")
     * @IsGranted("USER", subject="user")
     */
    public function structureRemove(User $user, StructureReferenceCollectionFacade $collection) : FacadeInterface
    {
        $this->validate($user, [
            new Unlocked(),
        ]);

        return $this->updateResourceCollection(
            Crud::DELETE(),
            Resource::USER(),
            $user,
            Resource::STRUCTURE(),
            $collection,
            'structure',
            ResourceOwnership::KNOWN_RESOURCE(),
            ResourceOwnership::KNOWN_RESOURCE()
        );
    }

    /**
     * Send a "password recovery" email to the given user.
     *
     * @Endpoint(
     *   priority = 340,
     *   response = @Facade(class = HttpNoContentFacade::class)
     * )
     * @Route(name="password_recovery", path="{email}/password-recovery", methods={"PUT"})
     * @Entity("user", expr="repository.findByUsernameAndCurrentPlatform(email)")
     * @IsGranted("USER", subject="user")
     */
    public function passwordRecovery(User $user)
    {
        $this->passwordRecoveryManager->sendPasswordRecoveryEmail(
            $user->getUsername()
        );

        return new HttpNoContentFacade();
    }

    /**
     * Lock a user.
     *
     * @Endpoint(
     *   priority = 345,
     *   response = @Facade(class = HttpNoContentFacade::class)
     * )
     * @Route(name="lock", path="/{email}/lock", methods={"PUT"})
     * @Entity("user", expr="repository.findByUsernameAndCurrentPlatform(email)")
     * @IsGranted("USER", subject="user")
     */
    public function lock(User $user)
    {
        $this->validate($user, [
            $this->getMeValidationCallback(),
        ]);

        $user->setLocked(true);

        $this->userManager->save($user);

        return new HttpNoContentFacade();
    }

    /**
     * Unlock a user.
     *
     * @Endpoint(
     *   priority = 350,
     *   response = @Facade(class = HttpNoContentFacade::class)
     * )
     * @Route(name="unlock", path="/{email}/unlock", methods={"PUT"})
     * @Entity("user", expr="repository.findByUsernameAndCurrentPlatform(email)")
     * @IsGranted("USER", subject="user")
     */
    public function unlock(User $user)
    {
        $this->validate($user, [
            $this->getMeValidationCallback(),
        ]);

        $user->setLocked(false);

        $this->userManager->save($user);

        return new HttpNoContentFacade();
    }

    private function getMeValidationCallback() : Callback
    {
        return new Callback(function ($object, ExecutionContextInterface $context) {
            /** @var User $object */
            if ($this->getSecurity()->getUser()->isEqualTo($object)) {
                $context->addViolation('Users are not allowed to update themselves.');
            }
        });
    }

    private function getRootValidationCallback() : Callback
    {
        return new Callback(function ($object, ExecutionContextInterface $context) {
            /** @var User $object */
            if (!$this->getSecurity()->getUser()->isRoot() && $object->isRoot()) {
                $context->addViolation('Only root users can set other users as root or update them');
            }
        });
    }
}