<?php

namespace App\Manager;

use App\Entity\UserInformation;
use App\Entity\Volunteer;
use App\Repository\VolunteerRepository;
use Doctrine\ORM\QueryBuilder;

class VolunteerManager
{
    /**
     * @var VolunteerRepository
     */
    private $volunteerRepository;

    /**
     * @var UserInformationManager
     */
    private $userInformationManager;

    /**
     * @param VolunteerRepository    $volunteerRepository
     * @param UserInformationManager $userInformationManager
     */
    public function __construct(VolunteerRepository $volunteerRepository,
        UserInformationManager $userInformationManager)
    {
        $this->volunteerRepository    = $volunteerRepository;
        $this->userInformationManager = $userInformationManager;
    }

    /**
     * @param int $volunteerId
     *
     * @return Volunteer|null
     */
    public function find(int $volunteerId): ?Volunteer
    {
        return $this->volunteerRepository->find($volunteerId);
    }

    /**
     * @return array
     */
    public function listVolunteerNivols(): array
    {
        return $this->volunteerRepository->listVolunteerNivols();
    }

    /**
     * @param string $nivol
     *
     * @return Volunteer|null
     */
    public function findOneByNivol(string $nivol): ?Volunteer
    {
        return $this->volunteerRepository->findOneByNivol($nivol);
    }

    /**
     * @param string $phoneNumber
     *
     * @return Volunteer|null
     */
    public function findOneByPhoneNumber(string $phoneNumber): ?Volunteer
    {
        return $this->volunteerRepository->findOneByPhoneNumber($phoneNumber);
    }

    /**
     * @param string $email
     *
     * @return Volunteer|null
     */
    public function findOneByEmail(string $email): ?Volunteer
    {
        return $this->volunteerRepository->findOneByEmail($email);
    }

    /**
     * @param Volunteer $volunteer
     */
    public function save(Volunteer $volunteer)
    {
        $this->volunteerRepository->save($volunteer);
    }

    /**
     * @param string|null $criteria
     *
     * @return Volunteer[]|array
     */
    public function searchAll(?string $criteria, int $limit)
    {
        return $this->volunteerRepository->searchAll($criteria, $limit);
    }

    /**
     * @param UserInformation $user
     * @param string|null     $criteria
     *
     * @return Volunteer[]|array
     */
    public function searchForCurrentUser(?string $criteria, int $limit)
    {
        return $this->volunteerRepository->searchForUser(
            $this->userInformationManager->findForCurrentUser(),
            $criteria,
            $limit
        );
    }

    /**
     * @param string $criteria
     *
     * @return QueryBuilder
     */
    public function searchAllQueryBuilder(?string $criteria): QueryBuilder
    {
        return $this->volunteerRepository->searchAllQueryBuilder($criteria);
    }

    /**
     * @param UserInformation $user
     * @param string          $criteria
     *
     * @return QueryBuilder
     */
    public function searchForCurrentUserQueryBuilder(?string $criteria): QueryBuilder
    {
        return $this->volunteerRepository->searchForUserQueryBuilder(
            $this->userInformationManager->findForCurrentUser(),
            $criteria
        );
    }

    /**
     * @param callable $callback
     * @param bool     $onlyEnabled
     *
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function foreach(callable $callback, bool $onlyEnabled = true)
    {
        $this->volunteerRepository->foreach($callback, $onlyEnabled);
    }

    /**
     * @return array
     */
    public function findIssues(): array
    {
        $volunteers = $this->volunteerRepository->getIssues(
            $this->userInformationManager->findForCurrentUser()
        );

        $issues = [
            'phones' => 0,
            'emails' => 0,
        ];

        foreach ($volunteers as $volunteer) {
            /** @var Volunteer $volunteer */
            if (!$volunteer->getPhoneNumber()) {
                $issues['phones']++;
            }
            if (!$volunteer->getEmail()) {
                $issues['emails']++;
            }
        }

        return $issues;
    }

    /**
     * @return array
     */
    public function getIssues(): array
    {
        return $this->volunteerRepository->getIssues(
            $this->userInformationManager->findForCurrentUser()
        );
    }
}