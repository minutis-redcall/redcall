<?php

namespace App\Controller\Api;

use App\Entity\Badge;
use App\Entity\Phone;
use App\Entity\Structure;
use App\Entity\User;
use App\Entity\Volunteer;
use App\Enum\Crud;
use App\Enum\Resource;
use App\Enum\ResourceOwnership;
use App\Facade\Badge\BadgeFacade;
use App\Facade\Badge\BadgeFiltersFacade;
use App\Facade\Badge\BadgeReferenceCollectionFacade;
use App\Facade\Badge\BadgeReferenceFacade;
use App\Facade\Generic\UpdateStatusFacade;
use App\Facade\Phone\PhoneFacade;
use App\Facade\Phone\PhoneReadFacade;
use App\Facade\Structure\StructureFacade;
use App\Facade\Structure\StructureFiltersFacade;
use App\Facade\Structure\StructureReferenceCollectionFacade;
use App\Facade\Structure\StructureReferenceFacade;
use App\Facade\Volunteer\VolunteerFacade;
use App\Facade\Volunteer\VolunteerFiltersFacade;
use App\Facade\Volunteer\VolunteerReadFacade;
use App\Manager\BadgeManager;
use App\Manager\PhoneManager;
use App\Manager\StructureManager;
use App\Manager\UserManager;
use App\Manager\VolunteerManager;
use App\Transformer\BadgeTransformer;
use App\Transformer\PhoneTransformer;
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
     * @var BadgeManager
     */
    private $badgeManager;

    /**
     * @var BadgeTransformer
     */
    private $badgeTransformer;

    /**
     * @var PhoneManager
     */
    private $phoneManager;

    /**
     * @var PhoneTransformer
     */
    private $phoneTransformer;

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
        BadgeManager $badgeManager,
        BadgeTransformer $badgeTransformer,
        PhoneManager $phoneManager,
        PhoneTransformer $phoneTransformer,
        StructureManager $structureManager,
        StructureTransformer $structureTransformer,
        UserManager $userManager)
    {
        $this->volunteerManager     = $volunteerManager;
        $this->volunteerTransformer = $volunteerTransformer;
        $this->badgeManager         = $badgeManager;
        $this->badgeTransformer     = $badgeTransformer;
        $this->phoneManager         = $phoneManager;
        $this->phoneTransformer     = $phoneTransformer;
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
     * Get a volunteer (by external id).
     *
     * @Endpoint(
     *   priority = 510,
     *   response = @Facade(class = VolunteerReadFacade::class)
     * )
     * @Route(name="read_by_external_id", path="/{externalId}", methods={"GET"},
     *                                    requirements={"externalId"="^[a-zA-Z0-9]{2,12}$"})
     * @Entity("volunteer", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("VOLUNTEER", subject="volunteer")
     */
    public function readByExternalId(Volunteer $volunteer)
    {
        return $this->volunteerTransformer->expose($volunteer);
    }

    /**
     * Get a volunteer (by red cross email).
     *
     * @Endpoint(
     *   priority = 511,
     *   response = @Facade(class = VolunteerReadFacade::class)
     * )
     * @Route(name="read_by_email", path="/{email}", methods={"GET"})
     * @Entity("volunteer", expr="repository.findOneByInternalEmailAndCurrentPlatform(email)")
     * @IsGranted("VOLUNTEER", subject="volunteer")
     */
    public function readByEmail(Volunteer $volunteer)
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
     * List volunteer's phones
     *
     * @Endpoint(
     *   priority = 525,
     *   response = @Facade(class     = CollectionFacade::class,
     *                      decorates = @Facade(class = PhoneReadFacade::class))
     * )
     * @Route(name="phone_records", path="/{externalId}/phone", methods={"GET"})
     * @Entity("volunteer", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("VOLUNTEER", subject="volunteer")
     */
    public function phoneRecords(Volunteer $volunteer)
    {
        $collection = new CollectionFacade();

        foreach ($volunteer->getPhones() as $phone) {
            $collection[] = $this->phoneTransformer->expose($phone);
        }

        return $collection;
    }

    /**
     * Add a phone to the volunteer
     *
     * @Endpoint(
     *   priority = 530,
     *   request  = @Facade(class = PhoneFacade::class),
     *   response = @Facade(class  = UpdateStatusFacade::class)
     * )
     * @Route(name="phone_add", path="/{externalId}/phone", methods={"POST"})
     * @Entity("volunteer", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("VOLUNTEER", subject="volunteer")
     */
    public function phoneAdd(Volunteer $volunteer, PhoneFacade $facade) : FacadeInterface
    {
        $e164 = $facade->getE164();

        if ($volunteer->hasPhoneNumber($e164)) {
            return new UpdateStatusFacade($e164, false, 'Volunteer already has this phone number');
        }

        $phone = $this->phoneManager->findOneByPhoneNumber($e164);

        if ($phone && $phone->getVolunteer()->getId() !== $volunteer->getId()) {
            return new UpdateStatusFacade($e164, false, 'Phone already taken by volunteer #%s', $phone->getVolunteer()->getExternalId());
        }

        $phone = $this->phoneTransformer->reconstruct($facade);
        $volunteer->addPhoneAndEnsureOnlyOneIsPreferred($phone);

        $this->validate($volunteer, [
            new Unlocked(),
        ]);

        $this->volunteerManager->save($volunteer);

        return new UpdateStatusFacade($e164);
    }

    /**
     * Update volunteer's phone settings (e.g. set it as preferred)
     *
     * @Endpoint(
     *   priority = 535,
     *   request  = @Facade(class = PhoneFacade::class),
     *   response = @Facade(class  = UpdateStatusFacade::class)
     * )
     * @Route(name="phone_update", path="/{externalId}/phone", methods={"PUT"})
     * @Entity("volunteer", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("VOLUNTEER", subject="volunteer")
     */
    public function phoneUpdate(Volunteer $volunteer, PhoneFacade $facade) : FacadeInterface
    {
        $e164 = $facade->getE164();

        if (!$volunteer->hasPhoneNumber($e164)) {
            return new UpdateStatusFacade($e164, false, 'Volunteer does not have this phone number');
        }

        $phone = $this->phoneManager->findOneByPhoneNumber($e164);
        if ($phone->isPreferred()) {
            return new UpdateStatusFacade($e164, false, 'Phone number is already volunteer\'s preferred one');
        }

        $volunteer->setPhoneAsPreferred($phone);

        $this->volunteerManager->save($volunteer);

        return new UpdateStatusFacade($e164);
    }

    /**
     * Remove one volunteer's phone
     *
     * @Endpoint(
     *   priority = 540,
     *   request  = @Facade(class = PhoneFacade::class),
     *   response = @Facade(class  = UpdateStatusFacade::class)
     * )
     * @Route(name="phone_remove", path="/{externalId}/phone/{e164}", methods={"DELETE"})
     * @Entity("volunteer", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @Entity("phone", expr="repository.findOneByE164(e164)")
     * @IsGranted("VOLUNTEER", subject="volunteer")
     */
    public function phoneRemove(Volunteer $volunteer, Phone $phone) : FacadeInterface
    {
        $e164 = $phone->getE164();

        if (!$volunteer->hasPhoneNumber($e164)) {
            return new UpdateStatusFacade($e164, false, 'Volunteer does not have this phone number');
        }

        $volunteer->removePhoneAndEnsureOneIsPreferred($phone);

        $this->volunteerManager->save($volunteer);

        return new UpdateStatusFacade($e164);
    }

    /**
     * List volunteer's badges
     *
     * @Endpoint(
     *   priority = 545,
     *   request  = @Facade(class     = BadgeFiltersFacade::class),
     *   response = @Facade(class     = QueryBuilderFacade::class,
     *                      decorates = @Facade(class = BadgeFacade::class))
     * )
     * @Route(name="badge_records", path="/{externalId}/badge", methods={"GET"})
     * @Entity("volunteer", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("VOLUNTEER", subject="volunteer")
     */
    public function badgeRecords(Volunteer $volunteer, BadgeFiltersFacade $filters)
    {
        $qb = $this->badgeManager->searchForVolunteerQueryBuilder($volunteer, $filters->getCriteria());

        return new QueryBuilderFacade($qb, $filters->getPage(), function (Badge $badge) {
            return $this->badgeTransformer->expose($badge);
        });
    }

    /**
     * Add a list of one of several badges to the volunteer.
     *
     * @Endpoint(
     *   priority = 550,
     *   request  = @Facade(class     = BadgeReferenceCollectionFacade::class,
     *                      decorates = @Facade(class = BadgeReferenceFacade::class)),
     *   response = @Facade(class     = CollectionFacade::class,
     *                      decorates = @Facade(class = UpdateStatusFacade::class))
     * )
     * @Route(name="badge_add", path="/{externalId}/badge", methods={"POST"})
     * @Entity("volunteer", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("VOLUNTEER", subject="volunteer")
     */
    public function badgeAdd(Volunteer $volunteer, BadgeReferenceCollectionFacade $collection) : FacadeInterface
    {
        $this->validate($volunteer, [
            new Unlocked(),
        ]);

        return $this->updateResourceCollection(
            Crud::CREATE(),
            Resource::VOLUNTEER(),
            $volunteer,
            Resource::BADGE(),
            $collection,
            'badge',
            ResourceOwnership::KNOWN_RESOURCE(),
            ResourceOwnership::KNOWN_RESOURCE(),
        );
    }

    /**
     * Remove one or several badges from the volunteer.
     *
     * @Endpoint(
     *   priority = 555,
     *   request  = @Facade(class     = BadgeReferenceCollectionFacade::class,
     *                      decorates = @Facade(class = BadgeReferenceFacade::class)),
     *   response = @Facade(class     = CollectionFacade::class,
     *                      decorates = @Facade(class = UpdateStatusFacade::class))
     * )
     * @Route(name="badge_remove", path="/{externalId}/badge", methods={"DELETE"})
     * @Entity("volunteer", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("VOLUNTEER", subject="volunteer")
     */
    public function badgeRemove(Volunteer $volunteer,
        BadgeReferenceCollectionFacade $collection) : FacadeInterface
    {
        $this->validate($volunteer, [
            new Unlocked(),
        ]);

        return $this->updateResourceCollection(
            Crud::DELETE(),
            Resource::VOLUNTEER(),
            $volunteer,
            Resource::BADGE(),
            $collection,
            'badge',
            ResourceOwnership::KNOWN_RESOURCE(),
            ResourceOwnership::KNOWN_RESOURCE(),
        );
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
     *   priority = 575,
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
     *   priority = 580,
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
     *   priority = 585,
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
     *   priority = 590,
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
     * Anonymize a volunteer.
     *
     * Anonymizing a volunteer removes all its private information except its external id,
     * that cannot be removed in order to keep the "user deleted" information.
     *
     * This action can be undone by:
     * - unlocking the anonymized volunteer
     * - resynchronizing your data source in order to repopulate missing information
     * All volunteer's messages and answers prior to the anonymization cannot be restored.
     *
     * @Endpoint(
     *   priority = 595,
     *   response = @Facade(class = HttpNoContentFacade::class)
     * )
     * @Route(name="anonymize", path="/{externalId}/anonymize", methods={"PUT"})
     * @Entity("volunteer", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("VOLUNTEER", subject="volunteer")
     */
    public function anonymize(Volunteer $volunteer)
    {
        $this->validate($volunteer, [
            //            new Unlocked(),
            $this->getHasUserValidationCallback(),
        ]);

        $this->volunteerManager->anonymize($volunteer);

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