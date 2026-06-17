<?php

namespace App\Manager;

use App\Entity\Structure;
use App\Entity\User;
use App\Enum\Resource;
use App\Repository\UserRepository;
use Bundles\PasswordLoginBundle\Manager\UserManager as BaseUserManager;
use Doctrine\ORM\QueryBuilder;

class UserManager extends BaseUserManager
{
    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @var StructureManager
     */
    private $structureManager;

    /**
     * @var UserAuditLogManager
     */
    private $userAuditLogManager;

    public function __construct(UserRepository $userRepository,
        VolunteerManager $volunteerManager,
        StructureManager $structureManager,
        UserAuditLogManager $userAuditLogManager)
    {
        parent::__construct($userRepository);

        $this->volunteerManager    = $volunteerManager;
        $this->structureManager    = $structureManager;
        $this->userAuditLogManager = $userAuditLogManager;
    }

    /**
     * @param VolunteerManager $campaignManager
     */
    #[\Symfony\Contracts\Service\Attribute\Required]
    public function setVolunteerManager(VolunteerManager $volunteerManager)
    {
        $this->volunteerManager = $volunteerManager;
    }

    /**
     * @param StructureManager $campaignManager
     */
    #[\Symfony\Contracts\Service\Attribute\Required]
    public function setStructureManager(StructureManager $structureManager)
    {
        $this->structureManager = $structureManager;
    }

    /**
     * @return User[]
     */
    public function findAll() : array
    {
        return $this->userRepository->findAll();
    }

    public function findAllWithStructure(Structure $structure) : array
    {
        return $this->userRepository->findAllWithStructure($structure);
    }

    public function changeLocale(User $user, string $locale)
    {
        $user->setLocale($locale);

        $this->userRepository->save($user);
    }

    /**
     * Assigns (or clears) the directory id (NIVOL) that identifies this
     * operator. The User no longer links to a Volunteer entity — the two are
     * related only by sharing this value. Display name is copied, best-effort,
     * from the matching directory record when one exists.
     */
    public function changeExternalId(User $user, ?string $externalId = null)
    {
        if ($user->isLocked()) {
            return;
        }

        if (!$externalId) {
            $user->setExternalId(null);
            $user->getStructures()->clear();

            $this->save($user);

            return;
        }

        $user->setExternalId($externalId);

        if ($volunteer = $this->volunteerManager->findOneByExternalId($externalId)) {
            $user->setFirstName($volunteer->getFirstName());
            $user->setLastName($volunteer->getLastName());
        }

        // https://minutis-support.atlassian.net/browse/SUPPORT-1421
        // structures are no longer derived from the volunteer's structures.

        $this->save($user);
    }

    public function findOneByExternalId(string $externalId) : ?User
    {
        return $this->userRepository->findOneByExternalId($externalId);
    }

    public function findOneTrustedByExternalId(?string $externalId) : ?User
    {
        return $this->userRepository->findOneTrustedByExternalId($externalId);
    }

    /**
     * @param string[] $externalIds
     *
     * @return array<string, User>
     */
    public function findTrustedByExternalIds(array $externalIds) : array
    {
        return $this->userRepository->findTrustedByExternalIds($externalIds);
    }

    public function getUserStructuresQueryBuilder(User $user) : QueryBuilder
    {
        return $this->structureManager->getStructuresQueryBuilderForUser(
            $user
        );
    }

    public function searchQueryBuilder(?string $criteria, ?bool $onlyAdmins) : QueryBuilder
    {
        return $this->userRepository->searchQueryBuilder($criteria, $onlyAdmins);
    }

    public function getRedCallUsersInStructure(Structure $structure, bool $includeChildren) : array
    {
        $users = $this->userRepository->getRedCallUsersInStructure($structure);

        if ($includeChildren) {
            foreach ($structure->getChildrenStructures() as $childStructure) {
                $users = array_merge($users, $this->userRepository->getRedCallUsersInStructure($childStructure));
            }
        }

        return array_unique($users, SORT_REGULAR);
    }

    /**
     * @throws \LogicException when the volunteer cannot be turned into a user
     */
    public function createUser(string $externalId, ?User $actor = null, ?string $cliLabel = null) : User
    {
        $volunteer = $this->volunteerManager->findOneByExternalId($externalId);

        if (!$volunteer) {
            throw new \LogicException('external id do not exist');
        }

        if (!$volunteer->getEmail()) {
            throw new \LogicException('volunteer has no email');
        }

        if ($this->findOneByUsername($volunteer->getEmail())) {
            throw new \LogicException('user having this email already exist');
        }

        if ($this->findOneByExternalId($externalId)) {
            throw new \LogicException('external id already connected to a user');
        }

        $user = new User();

        $user->setLocale('fr');
        $user->setTimezone('Europe/Paris');

        $user->setUsername($volunteer->getEmail());
        $user->setPassword('invalid hash');
        $user->setIsVerified(true);
        $user->setIsTrusted(true);
        $user->setExternalId($externalId);
        $user->setFirstName($volunteer->getFirstName());
        $user->setLastName($volunteer->getLastName());
        $this->save($user);

        $this->userAuditLogManager->logCreated($actor, $cliLabel, $user);

        return $user;
    }

    /**
     * @see Resource::getProviderMethod()
     */
    public function findOneByUsername(string $username) : ?User
    {
        return $this->userRepository->findOneByUsername($username);
    }
}