<?php

namespace App\Controller\Api;

use App\Entity\Structure;
use App\Entity\User;
use App\Entity\Volunteer;
use App\Enum\Crud;
use App\Enum\Resource;
use App\Enum\ResourceOwnership;
use App\Facade\Generic\UpdateStatusFacade;
use App\Facade\Structure\StructureFacade;
use App\Facade\Structure\StructureFiltersFacade;
use App\Facade\Structure\StructureReferenceCollectionFacade;
use App\Facade\Structure\StructureReferenceFacade;
use App\Facade\Volunteer\VolunteerFacade;
use App\Facade\Volunteer\VolunteerFiltersFacade;
use App\Facade\Volunteer\VolunteerReadFacade;
use App\Manager\StructureManager;
use App\Manager\UserManager;
use App\Manager\VolunteerManager;
use App\Transformer\StructureTransformer;
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
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

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

    /**
     * @var StructureManager
     */
    private $structureManager;

    /**
     * @var StructureTransformer
     */
    private $structureTransformer;

    /**
     * @var UserManager
     */
    private $userManager;

    public function __construct(VolunteerManager $volunteerManager,
        VolunteerTransformer $volunteerTransformer,
        StructureManager $structureManager,
        StructureTransformer $structureTransformer,
        UserManager $userManager)
    {
        $this->volunteerManager     = $volunteerManager;
        $this->volunteerTransformer = $volunteerTransformer;
        $this->structureManager     = $structureManager;
        $this->structureTransformer = $structureTransformer;
        $this->userManager          = $userManager;
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

        $this->saveUser(null, $volunteer->getUser());

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
        $oldUser = $volunteer->getUser();

        $this->volunteerTransformer->reconstruct($facade, $volunteer);

        $this->validate($volunteer, [
            new UniqueEntity(['externalId', 'platform']),
            new Unlocked(),
        ]);

        // Redetach volunteer->user relation if necessary
        $this->volunteerTransformer->reconstruct($facade, $volunteer);

        $this->volunteerManager->save($volunteer);

        $this->saveUser($oldUser, $volunteer->getUser());

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

    /**
     * List volunteer's structures
     *
     * @Endpoint(
     *   priority = 560,
     *   request  = @Facade(class     = StructureFiltersFacade::class),
     *   response = @Facade(class     = QueryBuilderFacade::class,
     *                      decorates = @Facade(class = StructureFacade::class))
     * )
     * @Route(name="structure_records", path="/{externalId}/structure", methods={"GET"})
     * @Entity("volunteer", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("VOLUNTEER", subject="volunteer")
     */
    public function structureRecords(Volunteer $volunteer, StructureFiltersFacade $filters)
    {
        $qb = $this->structureManager->searchForVolunteerQueryBuilder(
            $volunteer,
            $filters->getCriteria(),
            $filters->isOnlyEnabled()
        );

        return new QueryBuilderFacade($qb, $filters->getPage(), function (Structure $structure) {
            return $this->structureTransformer->expose($structure);
        });
    }

    /**
     * Put the volunteer into one or several structures. Note that volunteer will
     * also receive all children structures.
     *
     * @Endpoint(
     *   priority = 565,
     *   request  = @Facade(class     = StructureReferenceCollectionFacade::class,
     *                      decorates = @Facade(class = StructureReferenceFacade::class)),
     *   response = @Facade(class     = CollectionFacade::class,
     *                      decorates = @Facade(class = UpdateStatusFacade::class))
     * )
     * @Route(name="structure_add", path="/{externalId}/structure", methods={"POST"})
     * @Entity("volunteer", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("VOLUNTEER", subject="volunteer")
     */
    public function structureAdd(Volunteer $volunteer, StructureReferenceCollectionFacade $collection) : FacadeInterface
    {
        $this->validate($volunteer, [
            new Unlocked(),
        ]);

        return $this->updateResourceCollection(
            Crud::CREATE(),
            Resource::VOLUNTEER(),
            $volunteer,
            Resource::STRUCTURE(),
            $collection,
            'volunteer',
            ResourceOwnership::RESOLVED_RESOURCE(),
            ResourceOwnership::RESOLVED_RESOURCE(),
            function (Volunteer $knownResource, Structure $resolvedResource) {
                $this->structureManager->addStructureAndItsChildrenToVolunteer($this->getPlatform(), $knownResource, $resolvedResource);
            }
        );
    }

    /**
     * Remove one or several structures from the volunteer.
     *
     * @Endpoint(
     *   priority = 570,
     *   request  = @Facade(class     = StructureReferenceCollectionFacade::class,
     *                      decorates = @Facade(class = StructureReferenceFacade::class)),
     *   response = @Facade(class     = CollectionFacade::class,
     *                      decorates = @Facade(class = UpdateStatusFacade::class))
     * )
     * @Route(name="structure_remove", path="/{externalId}/structure", methods={"DELETE"})
     * @Entity("volunteer", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("VOLUNTEER", subject="volunteer")
     */
    public function structureRemove(Volunteer $volunteer,
        StructureReferenceCollectionFacade $collection) : FacadeInterface
    {
        $this->validate($volunteer, [
            new Unlocked(),
        ]);

        return $this->updateResourceCollection(
            Crud::DELETE(),
            Resource::VOLUNTEER(),
            $volunteer,
            Resource::STRUCTURE(),
            $collection,
            'volunteer',
            ResourceOwnership::RESOLVED_RESOURCE(),
            ResourceOwnership::RESOLVED_RESOURCE(),
        );
    }

    /**
     * Lock a volunteer.
     *
     * @Endpoint(
     *   priority = 580,
     *   response = @Facade(class = HttpNoContentFacade::class)
     * )
     * @Route(name="lock", path="/{externalId}/lock", methods={"PUT"})
     * @Entity("volunteer", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("VOLUNTEER", subject="volunteer")
     */
    public function lock(Volunteer $volunteer)
    {
        $volunteer->setLocked(true);

        $this->volunteerManager->save($volunteer);

        return new HttpNoContentFacade();
    }

    /**
     * Unlock a volunteer.
     *
     * @Endpoint(
     *   priority = 585,
     *   response = @Facade(class = HttpNoContentFacade::class)
     * )
     * @Route(name="unlock", path="/{externalId}/unlock", methods={"PUT"})
     * @Entity("volunteer", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("VOLUNTEER", subject="volunteer")
     */
    public function unlock(Volunteer $volunteer)
    {
        $volunteer->setLocked(false);

        $this->volunteerManager->save($volunteer);

        return new HttpNoContentFacade();
    }

    /**
     * Disable a volunteer.
     *
     * @Endpoint(
     *   priority = 590,
     *   response = @Facade(class = HttpNoContentFacade::class)
     * )
     * @Route(name="disable", path="/{externalId}/disable", methods={"PUT"})
     * @Entity("volunteer", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("VOLUNTEER", subject="volunteer")
     */
    public function disable(Volunteer $volunteer)
    {
        $this->validate($volunteer, [
            new Unlocked(),
            $this->getHasUserValidationCallback(),
        ]);

        $volunteer->setEnabled(false);

        $this->volunteerManager->save($volunteer);

        return new HttpNoContentFacade();
    }

    /**
     * Enable a volunteer.
     *
     * @Endpoint(
     *   priority = 595,
     *   response = @Facade(class = HttpNoContentFacade::class)
     * )
     * @Route(name="enable", path="/{externalId}/enable", methods={"PUT"})
     * @Entity("volunteer", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("VOLUNTEER", subject="volunteer")
     */
    public function enable(Volunteer $volunteer)
    {
        $this->validate($volunteer, [
            new Unlocked(),
        ]);

        $volunteer->setEnabled(true);

        $this->volunteerManager->save($volunteer);

        return new HttpNoContentFacade();
    }

    /**
     * User is the owning side of the Volunteer relation, it
     * should be persisted if there were some changes.
     */
    private function saveUser(?User $oldUser, ?User $newUser)
    {
        if ($oldUser !== $newUser) {
            if ($oldUser && !$oldUser->isLocked()) {
                $this->userManager->save($oldUser);
            }
            if ($newUser && !$newUser->isLocked()) {
                $this->userManager->save($newUser);
            }
        }
    }

    private function getHasUserValidationCallback() : Callback
    {
        return new Callback(function ($object, ExecutionContextInterface $context) {
            /** @var Volunteer $object */
            if ($object->getUser()) {
                $context
                    ->buildViolation('Volunteer cannot be disabled because it is bound to a User, remove the User first.')
                    ->setInvalidValue($object->getUser()->getUserIdentifier())
                    ->atPath('user')
                    ->addViolation();
            }
        });
    }
}