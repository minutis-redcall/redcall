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

    public function findAllWithStructure(Structure $structure) : array
    {
        return $this->userRepository->findAllWithStructure($structure);
    }

    public function changeLocale(User $user, string $locale)
    {
        $user->setLocale($locale);

        $this->userRepository->save($user);
    }

    public function changeVolunteer(User $user, ?string $volunteerExternalId = null)
    {
        if ($user->isLocked()) {
            return;
        }

        $volunteer = null;

        if ($volunteerExternalId) {
            $volunteer = $this->volunteerManager->findOneByExternalId($volunteerExternalId);
        }

        if (!$volunteer) {
            $user->setVolunteer(null);
            $user->getStructures()->clear();

            $this->save($user);

            return;
        }

        $user->setVolunteer($volunteer);

        // https://minutis-support.atlassian.net/browse/SUPPORT-1421
        // $structures = $this->structureManager->findCallableStructuresForVolunteer($volunteer);
        // $user->updateStructures($structures);

        $this->save($user);
    }

    public function findOneByExternalId(string $externalId) : ?User
    {
        return $this->userRepository->findOneByExternalId($externalId);
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

    public function createUser(string $externalId)
    {
        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command'      => 'user:create',
            'external-id'  => [$externalId],
        ]);

        $application->run($input, new NullOutput());
    }

    /**
     * @see Resource::getProviderMethod()
     */
    public function findOneByUsername(string $username) : ?User
    {
        return $this->userRepository->findOneByUsername($username);
    }
}