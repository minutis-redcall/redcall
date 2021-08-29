<?php

namespace App\Controller\Api;

use App\Entity\Structure;
use App\Entity\User;
use App\Entity\Volunteer;
use App\Enum\Crud;
use App\Enum\Resource;
use App\Enum\ResourceOwnership;
use App\Facade\Generic\UpdateStatusFacade;
use App\Facade\Resource\UserResourceFacade;
use App\Facade\Resource\VolunteerResourceFacade;
use App\Facade\Structure\StructureFacade;
use App\Facade\Structure\StructureFiltersFacade;
use App\Facade\Structure\StructureReadFacade;
use App\Facade\User\UserFiltersFacade;
use App\Facade\User\UserReferenceCollectionFacade;
use App\Facade\User\UserReferenceFacade;
use App\Facade\Volunteer\VolunteerFiltersFacade;
use App\Facade\Volunteer\VolunteerReferenceCollectionFacade;
use App\Facade\Volunteer\VolunteerReferenceFacade;
use App\Manager\StructureManager;
use App\Manager\UserManager;
use App\Manager\VolunteerManager;
use App\Transformer\ResourceTransformer;
use App\Transformer\StructureTransformer;
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
 * Structures are independent Red Cross sections that manage volunteers and organize operations.
 *
 * @Route("/api/structure", name="api_structure_")
 */
class StructureController extends BaseController
{
    /**
     * @var StructureManager
     */
    private $structureManager;

    /**
     * @var StructureTransformer
     */
    private $structureTransformer;

    /**
     * @var ResourceTransformer
     */
    private $resourceTransformer;

    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @var UserManager
     */
    private $userManager;

    public function __construct(StructureManager $structureManager,
        StructureTransformer $structureTransformer,
        ResourceTransformer $resourceTransformer,
        VolunteerManager $volunteerManager,
        UserManager $userManager)
    {
        $this->structureManager     = $structureManager;
        $this->structureTransformer = $structureTransformer;
        $this->resourceTransformer  = $resourceTransformer;
        $this->volunteerManager     = $volunteerManager;
        $this->userManager          = $userManager;
    }

    /**
     * List all structures.
     *
     * @Endpoint(
     *   priority = 400,
     *   request  = @Facade(class     = StructureFiltersFacade::class),
     *   response = @Facade(class     = QueryBuilderFacade::class,
     *                      decorates = @Facade(class = StructureReadFacade::class))
     * )
     * @Route(name="records", methods={"GET"})
     */
    public function records(StructureFiltersFacade $filters) : FacadeInterface
    {
        $qb = $this->structureManager->searchQueryBuilder($filters->getCriteria(), $filters->isOnlyEnabled());

        return new QueryBuilderFacade($qb, $filters->getPage(), function (Structure $structure) {
            return $this->structureTransformer->expose($structure);
        });
    }

    /**
     * Create a new structure.
     *
     * @Endpoint(
     *   priority = 405,
     *   request  = @Facade(class     = StructureFacade::class),
     *   response = @Facade(class     = HttpCreatedFacade::class)
     * )
     * @Route(name="create", methods={"POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function create(StructureFacade $facade) : FacadeInterface
    {
        $structure = $this->structureTransformer->reconstruct($facade);

        $this->validate($structure, [
            new UniqueEntity(['platform', 'externalId']),
        ], ['create']);

        $this->structureManager->save($structure);

        return new HttpCreatedFacade();
    }

    /**
     * Get a structure.
     *
     * @Endpoint(
     *   priority = 410,
     *   response = @Facade(class = StructureReadFacade::class)
     * )
     * @Route(name="read", path="/{externalId}", methods={"GET"})
     * @Entity("structure", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("STRUCTURE", subject="structure")
     */
    public function read(Structure $structure)
    {
        return $this->structureTransformer->expose($structure);
    }

    /**
     * Update a structure.
     *
     * @Endpoint(
     *   priority = 415,
     *   request  = @Facade(class = StructureFacade::class),
     *   response = @Facade(class = HttpNoContentFacade::class)
     * )
     * @Route(name="update", path="/{externalId}", methods={"PUT"})
     * @Entity("structure", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("STRUCTURE", subject="structure")
     * @IsGranted("ROLE_ADMIN")
     */
    public function update(Structure $structure, StructureFacade $facade)
    {
        $structure = $this->structureTransformer->reconstruct($facade, $structure);

        $this->validate($structure, [
            new UniqueEntity(['platform', 'externalId']),
            new Unlocked(),
        ]);

        $this->structureManager->save($structure);

        return new HttpNoContentFacade();
    }

    /**
     * List volunteers that can be triggered in the given structure.
     *
     * @Endpoint(
     *   priority = 420,
     *   request  = @Facade(class     = VolunteerFiltersFacade::class),
     *   response = @Facade(class     = QueryBuilderFacade::class,
     *                      decorates = @Facade(class = VolunteerResourceFacade::class))
     * )
     * @Route(name="volunteer_records", path="/{externalId}/volunteer", methods={"GET"})
     * @Entity("structure", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("STRUCTURE", subject="structure")
     */
    public function volunteerRecords(Structure $structure, VolunteerFiltersFacade $filters)
    {
        $qb = $this->volunteerManager->searchInStructureQueryBuilder(
            $this->getPlatform(),
            $structure,
            $filters->getCriteria(),
            $filters->isOnlyEnabled(),
            $filters->isOnlyUsers()
        );

        return new QueryBuilderFacade($qb, $filters->getPage(), function (Volunteer $volunteer) {
            return $this->resourceTransformer->expose($volunteer);
        });
    }

    /**
     * Add one or several volunteers into the structure (volunteers are triggered by users).
     *
     * @Endpoint(
     *   priority = 425,
     *   request  = @Facade(class     = VolunteerReferenceCollectionFacade::class,
     *                      decorates = @Facade(class = VolunteerReferenceFacade::class)),
     *   response = @Facade(class     = CollectionFacade::class,
     *                      decorates = @Facade(class = UpdateStatusFacade::class))
     * )
     * @Route(name="volunteer_add", path="/{externalId}/volunteer", methods={"POST"})
     * @Entity("structure", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("STRUCTURE", subject="structure")
     */
    public function volunteerAdd(Structure $structure, VolunteerReferenceCollectionFacade $collection) : FacadeInterface
    {
        $this->validate($structure, [
            new Unlocked(),
        ]);

        return $this->updateResourceCollection(
            Crud::CREATE(),
            Resource::STRUCTURE(),
            $structure,
            Resource::VOLUNTEER(),
            $collection,
            'structure',
            ResourceOwnership::RESOLVED_RESOURCE(),
            ResourceOwnership::KNOWN_RESOURCE()
        );
    }

    /**
     * Remove one or several volunteers from the structure.
     *
     * @Endpoint(
     *   priority = 430,
     *   request  = @Facade(class     = VolunteerReferenceCollectionFacade::class,
     *                      decorates = @Facade(class = VolunteerReferenceFacade::class)),
     *   response = @Facade(class     = CollectionFacade::class,
     *                      decorates = @Facade(class = UpdateStatusFacade::class))
     * )
     * @Route(name="volunteer_remove", path="/{externalId}/volunteer", methods={"DELETE"})
     * @Entity("structure", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("STRUCTURE", subject="structure")
     */
    public function volunteerRemove(Structure $structure,
        VolunteerReferenceCollectionFacade $collection) : FacadeInterface
    {
        $this->validate($structure, [
            new Unlocked(),
        ]);

        return $this->updateResourceCollection(
            Crud::DELETE(),
            Resource::STRUCTURE(),
            $structure,
            Resource::VOLUNTEER(),
            $collection,
            'structure',
            ResourceOwnership::RESOLVED_RESOURCE(),
            ResourceOwnership::KNOWN_RESOURCE()
        );
    }

    /**
     * List RedCall users that can trigger volunteers in the given structure.
     *
     * @Endpoint(
     *   priority = 435,
     *   request  = @Facade(class     = UserFiltersFacade::class),
     *   response = @Facade(class     = QueryBuilderFacade::class,
     *                      decorates = @Facade(class = UserResourceFacade::class))
     * )
     * @Route(name="user_records", path="/{externalId}/user", methods={"GET"})
     * @Entity("structure", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("STRUCTURE", subject="structure")
     */
    public function userRecords(Structure $structure, UserFiltersFacade $filters)
    {
        $qb = $this->userManager->searchInStructureQueryBuilder(
            $this->getPlatform(),
            $structure,
            $filters->getCriteria(),
            $filters->isOnlyAdmins(),
            $filters->isOnlyDevelopers()
        );

        return new QueryBuilderFacade($qb, $filters->getPage(), function (User $user) {
            return $this->resourceTransformer->expose($user);
        });
    }

    /**
     * Add one or several users into the structure (users will trigger volunteers).
     *
     * @Endpoint(
     *   priority = 440,
     *   request  = @Facade(class     = UserReferenceCollectionFacade::class,
     *                      decorates = @Facade(class = UserReferenceFacade::class)),
     *   response = @Facade(class     = CollectionFacade::class,
     *                      decorates = @Facade(class = UpdateStatusFacade::class))
     * )
     * @Route(name="user_add", path="/{externalId}/user", methods={"POST"})
     * @Entity("structure", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("STRUCTURE", subject="structure")
     */
    public function userAdd(Structure $structure, UserReferenceCollectionFacade $collection) : FacadeInterface
    {
        $this->validate($structure, [
            new Unlocked(),
        ]);

        return $this->updateResourceCollection(
            Crud::CREATE(),
            Resource::STRUCTURE(),
            $structure,
            Resource::USER(),
            $collection,
            'user',
            ResourceOwnership::KNOWN_RESOURCE(),
            ResourceOwnership::RESOLVED_RESOURCE()
        );
    }

    /**
     * Remove one or several users from the structure.
     *
     * @Endpoint(
     *   priority = 445,
     *   request  = @Facade(class     = UserReferenceCollectionFacade::class,
     *                      decorates = @Facade(class = UserReferenceFacade::class)),
     *   response = @Facade(class     = CollectionFacade::class,
     *                      decorates = @Facade(class = UpdateStatusFacade::class))
     * )
     * @Route(name="user_remove", path="/{externalId}/user", methods={"DELETE"})
     * @Entity("structure", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("STRUCTURE", subject="structure")
     */
    public function userRemove(Structure $structure, UserReferenceCollectionFacade $collection) : FacadeInterface
    {
        $this->validate($structure, [
            new Unlocked(),
        ]);

        return $this->updateResourceCollection(
            Crud::DELETE(),
            Resource::STRUCTURE(),
            $structure,
            Resource::USER(),
            $collection,
            'user',
            ResourceOwnership::KNOWN_RESOURCE(),
            ResourceOwnership::RESOLVED_RESOURCE()
        );
    }

    /**
     * Lock a structure.
     *
     * @Endpoint(
     *   priority = 450,
     *   response = @Facade(class = HttpNoContentFacade::class)
     * )
     * @Route(name="lock", path="/{externalId}/lock", methods={"PUT"})
     * @Entity("structure", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("STRUCTURE", subject="structure")
     */
    public function lock(Structure $structure)
    {
        $structure->setLocked(true);

        $this->structureManager->save($structure);

        return new HttpNoContentFacade();
    }

    /**
     * Unlock a structure.
     *
     * @Endpoint(
     *   priority = 455,
     *   response = @Facade(class = HttpNoContentFacade::class)
     * )
     * @Route(name="unlock", path="/{externalId}/unlock", methods={"PUT"})
     * @Entity("structure", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("STRUCTURE", subject="structure")
     */
    public function unlock(Structure $structure)
    {
        $structure->setLocked(false);

        $this->structureManager->save($structure);

        return new HttpNoContentFacade();
    }

    /**
     * Disable a structure.
     *
     * @Endpoint(
     *   priority = 460,
     *   response = @Facade(class = HttpNoContentFacade::class)
     * )
     * @Route(name="disable", path="/{externalId}/disable", methods={"PUT"})
     * @Entity("structure", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("STRUCTURE", subject="structure")
     */
    public function disable(Structure $structure)
    {
        $this->validate($structure, [
            new Unlocked(),
        ]);

        $structure->setEnabled(false);

        $this->structureManager->save($structure);

        return new HttpNoContentFacade();
    }

    /**
     * Enable a structure.
     *
     * @Endpoint(
     *   priority = 465,
     *   response = @Facade(class = HttpNoContentFacade::class)
     * )
     * @Route(name="enable", path="/{externalId}/enable", methods={"PUT"})
     * @Entity("structure", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("STRUCTURE", subject="structure")
     */
    public function enable(Structure $structure)
    {
        $this->validate($structure, [
            new Unlocked(),
        ]);

        $structure->setEnabled(true);

        $this->structureManager->save($structure);

        return new HttpNoContentFacade();
    }
}