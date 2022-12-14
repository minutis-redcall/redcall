<?php

namespace App\Manager;

use App\Entity\Structure;
use App\Entity\User;
use App\Enum\Resource;
use App\Repository\UserRepository;
use Bundles\PasswordLoginBundle\Manager\UserManager as BaseUserManager;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpKernel\KernelInterface;

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
     * @var KernelInterface
     */
    private $kernel;

    public function __construct(UserRepository $userRepository,
        VolunteerManager $volunteerManager,
        StructureManager $structureManager,
        KernelInterface $kernel)
    {
        parent::__construct($userRepository);

        $this->volunteerManager = $volunteerManager;
        $this->structureManager = $structureManager;
        $this->kernel           = $kernel;
    }

    /**
     * @required
     *
     * @param VolunteerManager $campaignManager
     */
    public function setVolunteerManager(VolunteerManager $volunteerManager)
    {
        $this->volunteerManager = $volunteerManager;
    }

    /**
     * @required
     *
     * @param StructureManager $campaignManager
     */
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

    public function findOneByExternalId(string $platform, string $externalId) : ?User
    {
        return $this->userRepository->findOneByExternalId($platform, $externalId);
    }

    public function changeLocale(User $user, string $locale)
    {
        $user->setLocale($locale);

        $this->userRepository->save($user);
    }

    public function changeVolunteer(User $user, ?string $volunteerPlatform = null, ?string $volunteerExternalId = null)
    {
        if ($user->isLocked()) {
            return;
        }

        $volunteer = null;

        if ($volunteerPlatform && $volunteerExternalId) {
            $volunteer = $this->volunteerManager->findOneByExternalId($user->getPlatform(), $volunteerExternalId);
        }

        if (!$volunteer) {
            $user->setVolunteer(null);
            $user->getStructures()->clear();

            $this->save($user);

            return;
        }

        $user->setVolunteer($volunteer);

        // https://minutis-support.atlassian.net/browse/SUPPORT-1421
        // $structures = $this->structureManager->findCallableStructuresForVolunteer($volunteerPlatform, $volunteer);
        // $user->updateStructures($structures);

        $this->save($user);
    }

    public function getUserStructuresQueryBuilder(string $platform, User $user) : QueryBuilder
    {
        return $this->structureManager->getStructuresQueryBuilderForUser(
            $platform,
            $user
        );
    }

    public function searchQueryBuilder(?string $criteria, ?bool $onlyAdmins, ?bool $onlyDevelopers) : QueryBuilder
    {
        return $this->userRepository->searchQueryBuilder($criteria, $onlyAdmins, $onlyDevelopers);
    }

    public function getRedCallUsersInStructure(Structure $structure) : array
    {
        return $this->userRepository->getRedCallUsersInStructure($structure);
    }

    public function createUser(string $platform, string $externalId)
    {
        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command'     => 'user:create',
            'platform'    => $platform,
            'external-id' => [$externalId],
        ]);

        $application->run($input, new NullOutput());
    }

    public function getUserCountInStructure(Structure $structure) : int
    {
        return $this->userRepository->getUserCountInStructure($structure);
    }

    /**
     * @see Resource::getProviderMethod()
     */
    public function findOneByUsernameAndPlatform(string $platform, string $username) : ?User
    {
        return $this->userRepository->findOneByUsernameAndPlatform($platform, $username);
    }

    public function searchInStructureQueryBuilder(string $platform,
        Structure $structure,
        ?string $criteria,
        bool $onlyAdmins,
        bool $onlyDevelopers) : QueryBuilder
    {
        return $this->userRepository->searchInStructureQueryBuilder($platform, $structure, $criteria, $onlyAdmins, $onlyDevelopers);
    }
}