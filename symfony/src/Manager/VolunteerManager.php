<?php

namespace App\Manager;

use App\Entity\Answer;
use App\Entity\Badge;
use App\Entity\GeoLocation;
use App\Entity\Message;
use App\Entity\Structure;
use App\Entity\Volunteer;
use App\Repository\VolunteerRepository;
use App\Security\Helper\Security;
use Doctrine\ORM\QueryBuilder;

class VolunteerManager
{
    /**
     * @var VolunteerRepository
     */
    private $volunteerRepository;

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var AnswerManager
     */
    private $answerManager;

    /**
     * @var GeoLocationManager
     */
    private $geoLocationManager;

    /**
     * @var PhoneManager
     */
    private $phoneManager;

    /**
     * @var Security
     */
    private $security;

    public function __construct(VolunteerRepository $volunteerRepository,
        AnswerManager $answerManager,
        GeoLocationManager $geoLocationManager,
        PhoneManager $phoneManager,
        Security $security)
    {
        $this->volunteerRepository = $volunteerRepository;
        $this->answerManager       = $answerManager;
        $this->geoLocationManager  = $geoLocationManager;
        $this->phoneManager        = $phoneManager;
        $this->security            = $security;
    }

    /**
     * @required
     */
    public function setUserManager(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    public function find(int $volunteerId) : ?Volunteer
    {
        return $this->volunteerRepository->find($volunteerId);
    }

    public function findOneByExternalId(string $platform, string $externalId) : ?Volunteer
    {
        return $this->volunteerRepository->findOneByExternalId($platform, $externalId);
    }

    public function findOneByPhoneNumber(string $phoneNumber) : ?Volunteer
    {
        $phone = $this->phoneManager->findOneByPhoneNumber($phoneNumber);

        return $phone ? $phone->getVolunteer() : null;
    }

    public function findOneByEmail(string $email) : ?Volunteer
    {
        return $this->volunteerRepository->findOneByEmail($email);
    }

    public function save(Volunteer $volunteer)
    {
        $this->volunteerRepository->save($volunteer);

        return $volunteer;
    }

    public function searchAll(?string $criteria, int $limit)
    {
        return $this->volunteerRepository->searchAll($this->security->getPlatform(), $criteria, $limit);
    }

    public function searchForCurrentUser(?string $criteria, int $limit, bool $onlyEnabled = false)
    {
        return $this->volunteerRepository->searchForUser(
            $this->security->getUser(),
            $criteria,
            $limit,
            $onlyEnabled
        );
    }

    public function getVolunteerList(string $platform, array $volunteerIds, bool $onlyEnabled = true) : array
    {
        $volunteers = [];

        $list = $this->volunteerRepository->getVolunteerList($platform, $volunteerIds, $onlyEnabled);

        usort($list, function (Volunteer $a, Volunteer $b) {
            return $a->getLastName() <=> $b->getLastName();
        });

        foreach ($list as $volunteer) {
            /** @var Volunteer $volunteer */
            $volunteers[$volunteer->getId()] = $volunteer;
        }

        return $volunteers;
    }

    public function getVolunteerListForCurrentUser(array $volunteerIds) : array
    {
        return $this->volunteerRepository->getVolunteerListForUser(
            $this->security->getUser(),
            $volunteerIds
        );
    }

    public function searchInStructureQueryBuilder(string $platform,
        Structure $structure,
        ?string $criteria,
        bool $onlyEnabled,
        bool $onlyUsers)
    {
        return $this->volunteerRepository->searchInStructureQueryBuilder($platform, $structure, $criteria, $onlyEnabled, $onlyUsers);
    }

    public function searchAllQueryBuilder(string $platform,
        ?string $criteria,
        bool $onlyEnabled,
        bool $onlyUsers) : QueryBuilder
    {
        return $this->volunteerRepository->searchAllWithFiltersQueryBuilder($platform, $criteria, $onlyEnabled, $onlyUsers);
    }

    public function searchForCurrentUserQueryBuilder(?string $criteria,
        bool $onlyEnabled,
        bool $onlyUsers) : QueryBuilder
    {
        return $this->volunteerRepository->searchForUserQueryBuilder(
            $this->security->getUser(),
            $criteria,
            $onlyEnabled,
            $onlyUsers
        );
    }

    public function foreach(callable $callback, bool $onlyEnabled = true)
    {
        $this->volunteerRepository->foreach($callback, $onlyEnabled);
    }

    public function getIssues() : array
    {
        return $this->volunteerRepository->getIssues(
            $this->security->getUser()
        );
    }

    public function synchronizeWithPegass()
    {
        $this->volunteerRepository->synchronizeWithPegass();
    }

    public function getIdsByExternalIds(array $externalIds) : array
    {
        return array_column(
            $this->volunteerRepository->getIdsByExternalIds(
                array_map(function ($externalId) {
                    return ltrim($externalId, '0');
                }, $externalIds)
            ),
            'id'
        );
    }

    public function getVolunteerListInStructures(array $structureIds) : array
    {
        return array_column($this->volunteerRepository->getVolunteerListInStructures($structureIds), 'id');
    }

    public function getVolunteerCountInStructures(array $structureIds) : int
    {
        return $this->volunteerRepository->getVolunteerCountInStructures($structureIds);
    }

    public function getVolunteerListInStructuresHavingBadges(array $structureIds, array $badgeIds) : array
    {
        return array_column($this->volunteerRepository->getVolunteerListInStructuresHavingBadges($structureIds, $badgeIds), 'id');
    }

    public function getVolunteerCountInStructuresHavingBadges(array $structureIds, array $badgeIds) : int
    {
        return $this->volunteerRepository->getVolunteerCountInStructuresHavingBadges($structureIds, $badgeIds);
    }

    public function getVolunteerCountHavingBadgesQueryBuilder(array $badgeIds) : int
    {
        return $this->volunteerRepository->getVolunteerCountHavingBadgesQueryBuilder($badgeIds);
    }

    public function getVolunteerGlobalCounts(array $structureIds) : int
    {
        return $this->volunteerRepository->getVolunteerGlobalCounts($structureIds);
    }

    public function filterInvalidExternalIds(string $platform, array $externalIds) : array
    {
        return $this->volunteerRepository->filterInvalidExternalIds(
            $platform,
            array_map(function ($externalId) {
                return ltrim($externalId, '0');
            }, $externalIds)
        );
    }

    public function filterInaccessibles(array $volunteerIds) : array
    {
        return $this->volunteerRepository->filterInaccessibles(
            $this->security->getUser(),
            $volunteerIds
        );
    }

    public function filterDisabled(string $platform, array $volunteerIds) : array
    {
        return array_column($this->volunteerRepository->filterDisabled($platform, $volunteerIds), 'id');
    }

    public function filterOptoutUntil(string $platform, array $volunteerIds) : array
    {
        return array_column($this->volunteerRepository->filterOptoutUntil($platform, $volunteerIds), 'id');
    }

    public function filterPhoneLandline(string $platform, array $volunteerIds) : array
    {
        return array_column($this->volunteerRepository->filterPhoneLandline($platform, $volunteerIds), 'id');
    }

    public function filterPhoneMissing(string $platform, array $volunteerIds) : array
    {
        return array_column($this->volunteerRepository->filterPhoneMissing($platform, $volunteerIds), 'id');
    }

    public function filterPhoneOptout(string $platform, array $volunteerIds) : array
    {
        return array_column($this->volunteerRepository->filterPhoneOptout($platform, $volunteerIds), 'id');
    }

    public function filterEmailMissing(string $platform, array $volunteerIds) : array
    {
        return array_column($this->volunteerRepository->filterEmailMissing($platform, $volunteerIds), 'id');
    }

    public function filterEmailOptout(string $platform, array $volunteerIds) : array
    {
        return array_column($this->volunteerRepository->filterEmailOptout($platform, $volunteerIds), 'id');
    }

    public function anonymize(Volunteer $volunteer)
    {
        foreach ($volunteer->getMessages() as $message) {
            /** @var Message $message */
            foreach ($message->getAnswers() ?? [] as $answer) {
                /** @var Answer $answer */
                if (!$answer->getByAdmin()) {
                    $answer->setRaw('');
                    $answer->setUnclear(false);
                    $answer->getChoices()->clear();
                    $this->answerManager->save($answer);
                }
            }

            if ($geo = $message->getGeoLocation()) {
                /** @var GeoLocation $geo */
                $geo->setLongitude('0.0');
                $geo->setLatitude('0.0');
                $geo->setAccuracy(0);
                $geo->setHeading(0);
                $this->geoLocationManager->save($geo);
            }
        }

        if ($user = $volunteer->getUser()) {
            $this->userManager->remove($user);
        }

        $volunteer->setFirstName('-');
        $volunteer->setLastName('-');
        $volunteer->getPhones()->clear();
        $volunteer->setEmail(null);
        $volunteer->setEnabled(false);
        $volunteer->setLocked(true);
        $volunteer->getBadges()->clear();
        $volunteer->setLastPegassUpdate(new \DateTime('2000-01-01'));
        $volunteer->setReport([]);
        $volunteer->getStructures()->clear();
        $volunteer->setUser(null);
        $volunteer->setPhoneNumberOptin(true);
        $volunteer->setPhoneNumberLocked(false);
        $volunteer->setEmailOptin(true);
        $volunteer->setEmailLocked(false);

        $this->save($volunteer);
    }

    public function orderVolunteerIdsByTriggeringPriority(array $volunteerIds) : array
    {
        $rows = $this->volunteerRepository->getVolunteerTriggeringPriorities($volunteerIds);

        $priorities = array_combine(
            array_column($rows, 'id'),
            array_column($rows, 'priority')
        );

        asort($priorities);

        $priorities = array_keys($priorities);

        $noBadges = array_diff($volunteerIds, $priorities);

        return array_merge($priorities, $noBadges);
    }

    public function getVolunteerCountInStructure(Structure $structure) : int
    {
        return $this->volunteerRepository->getVolunteerCountInStructure($structure);
    }

    public function getVolunteersHavingBadgeQueryBuilder(Badge $badge)
    {
        return $this->volunteerRepository->getVolunteersHavingBadgeQueryBuilder($badge);
    }
}