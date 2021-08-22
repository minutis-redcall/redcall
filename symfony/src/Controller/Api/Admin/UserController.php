<?php

namespace App\Controller\Api\Admin;

use App\Entity\Structure;
use App\Entity\User;
use App\Enum\Crud;
use App\Facade\Generic\UpdateStatusFacade;
use App\Facade\PageFilterFacade;
use App\Facade\Structure\StructureReferenceCollectionFacade;
use App\Facade\Structure\StructureReferenceFacade;
use App\Facade\User\UserFacade;
use App\Facade\User\UserFiltersFacade;
use App\Facade\User\UserReadFacade;
use App\Manager\StructureManager;
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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
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

    /**
     * @var ResourceTransformer
     */
    private $resourceTransformer;

    /**
     * @var StructureManager
     */
    private $structureManager;

    public function __construct(UserManager $userManager,
        UserTransformer $userTransformer,
        ResourceTransformer $resourceTransformer,
        StructureManager $structureManager)
    {
        $this->userManager         = $userManager;
        $this->userTransformer     = $userTransformer;
        $this->resourceTransformer = $resourceTransformer;
        $this->structureManager    = $structureManager;
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
     *   priority = 203,
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

    /**
     * Get a user.
     *
     * @Endpoint(
     *   priority = 206,
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
     *   priority = 209,
     *   request  = @Facade(class = UserFacade::class),
     *   response = @Facade(class = HttpNoContentFacade::class)
     * )
     * @Route(name="update", path="/{email}", methods={"PUT"})
     * @Entity("user", expr="repository.findByUsernameAndCurrentPlatform(email)")
     * @IsGranted("USER", subject="user")
     */
    public function update(User $user, UserFacade $facade)
    {
        $user = $this->userTransformer->reconstruct($facade, $user);

        $this->validate($user, [
            new UniqueEntity(['username']),
            $this->getMeValidationCallback(),
            $this->getRootValidationCallback(),
        ]);

        $this->userManager->save($user);

        return new HttpNoContentFacade();
    }

    /**
     * Delete a user.
     *
     * @Endpoint(
     *   priority = 212,
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
     *   priority = 215,
     *   request  = @Facade(class     = PageFilterFacade::class),
     *   response = @Facade(class     = QueryBuilderFacade::class,
     *                      decorates = @Facade(class = StructureReferenceFacade::class))
     * )
     * @Route(name="structure_records", path="/structure/{email}", methods={"GET"})
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
     *   priority = 218,
     *   request  = @Facade(class     = StructureReferenceCollectionFacade::class,
     *                      decorates = @Facade(class = StructureReferenceFacade::class)),
     *   response = @Facade(class     = CollectionFacade::class,
     *                      decorates = @Facade(class = UpdateStatusFacade::class))
     * )
     * @Route(name="structure_add", path="/structure/{email}", methods={"POST"})
     * @Entity("user", expr="repository.findByUsernameAndCurrentPlatform(email)")
     * @IsGranted("USER", subject="user")
     */
    public function structureAdd(User $user, StructureReferenceCollectionFacade $collection) : FacadeInterface
    {
        $this->validate($user, [
            new Unlocked(),
        ]);

        return $this->bulkUpdateStructures($user, $collection, Crud::CREATE());
    }

    /**
     * Remove one or several structures from user's scope.
     *
     * @Endpoint(
     *   priority = 221,
     *   request  = @Facade(class     = StructureReferenceCollectionFacade::class,
     *                      decorates = @Facade(class = StructureReferenceFacade::class)),
     *   response = @Facade(class     = CollectionFacade::class,
     *                      decorates = @Facade(class = UpdateStatusFacade::class))
     * )
     * @Route(name="structure_remove", path="/structure/{email}", methods={"DELETE"})
     * @Entity("user", expr="repository.findByUsernameAndCurrentPlatform(email)")
     * @IsGranted("USER", subject="user")
     */
    public function structureRemove(User $user, StructureReferenceCollectionFacade $collection) : FacadeInterface
    {
        $this->validate($user, [
            new Unlocked(),
        ]);

        return $this->bulkUpdateStructures($user, $collection, Crud::DELETE());
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

    private function bulkUpdateStructures(User $user, StructureReferenceCollectionFacade $collection, Crud $action)
    {
        $response = new CollectionFacade();
        $changes  = 0;

        foreach ($collection->getEntries() as $entry) {
            /** @var StructureReferenceFacade $entry */
            $structure = $this->structureManager->findOneByExternalId($this->getPlatform(), $entry->getExternalId());

            if (null === $structure) {
                $response[] = new UpdateStatusFacade($entry->getExternalId(), false, 'Structure does not exist');
                continue;
            }

            if (!$this->isGranted('STRUCTURE', $structure)) {
                $response[] = new UpdateStatusFacade($entry->getExternalId(), false, 'Access denied');
                continue;
            }

            switch ($action) {
                case Crud::CREATE():
                    if ($user->hasStructure($structure)) {
                        $response[] = new UpdateStatusFacade($entry->getExternalId(), false, 'User already have that structure');
                        continue 2;
                    }

                    $structuresToAdd = $this->structureManager->findCallableStructuresForStructure($this->getPlatform(), $structure);
                    foreach ($structuresToAdd as $structureToAdd) {
                        $user->addStructure($structureToAdd);
                    }

                    $response[] = new UpdateStatusFacade($entry->getExternalId(), true, sprintf('Added %d structure(s)', count($structuresToAdd)));

                    break;
                case Crud::DELETE():
                    if (!$user->hasStructure($structure)) {
                        $response[] = new UpdateStatusFacade($entry->getExternalId(), false, 'User does not have that structure');
                        continue 2;
                    }

                    $user->removeStructure($structure);

                    $response[] = new UpdateStatusFacade($entry->getExternalId());

                    break;
            }

            $changes++;

            break;
        }

        if ($changes) {
            $this->userManager->save($user);
        }

        return $response;
    }
}