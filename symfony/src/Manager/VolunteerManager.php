<?php

namespace App\Manager;

use App\Entity\Volunteer;
use App\Repository\VolunteerRepository;
use Bundles\PasswordLoginBundle\Entity\User;

class VolunteerManager
{
    /**
     * @var VolunteerRepository
     */
    private $volunteerRepository;

    /**
     * @param VolunteerRepository $volunteerRepository
     */
    public function __construct(VolunteerRepository $volunteerRepository)
    {
        $this->volunteerRepository = $volunteerRepository;
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
     * @param Volunteer $volunteer
     */
    public function save(Volunteer $volunteer)
    {
        $this->volunteerRepository->save($volunteer);
    }

    /**
     * @param string    $keyword
     * @param int       $maxResults
     * @param User|null $user
     *
     * @return array
     */
    public function search(string $keyword, int $maxResults, User $user = null): array
    {
        return $this->volunteerRepository->search($keyword, $maxResults, $user);
    }

    /**
     * @param User $user
     *
     * @return array
     */
    public function getVolunteersCountByTagsForUser(User $user): array
    {
        return $this->volunteerRepository->getVolunteersCountByTagsForUser($user);
    }

    /**
     * @param User $user
     *
     * @return array
     */
    public function findCallableForUser(User $user): array
    {
        return $this->volunteerRepository->findCallableForUser($user);
    }
}