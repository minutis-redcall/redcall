<?php

namespace App\Manager;

use App\Entity\Answer;
use App\Entity\GeoLocation;
use App\Entity\Message;
use App\Entity\Structure;
use App\Entity\Volunteer;
use App\Repository\VolunteerRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Contracts\Translation\TranslatorInterface;

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
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(VolunteerRepository $volunteerRepository,
        AnswerManager $answerManager,
        GeoLocationManager $geoLocationManager,
        PhoneManager $phoneManager,
        TranslatorInterface $translator)
    {
        $this->volunteerRepository = $volunteerRepository;
        $this->answerManager       = $answerManager;
        $this->geoLocationManager  = $geoLocationManager;
        $this->phoneManager        = $phoneManager;
        $this->translator          = $translator;
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

    public function findOneByNivol(string $nivol) : ?Volunteer
    {
        return $this->volunteerRepository->findOneByNivol($nivol);
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
        return $this->volunteerRepository->searchAll($criteria, $limit);
    }

    public function searchForCurrentUser(?string $criteria, int $limit, bool $onlyEnabled = false)
    {
        return $this->volunteerRepository->searchForUser(
            $this->userManager->findForCurrentUser(),
            $criteria,
            $limit,
            $onlyEnabled
        );
    }

    public function getVolunteerList(array $volunteerIds, bool $onlyEnabled = true) : array
    {
        $volunteers = [];

        $list = $this->volunteerRepository->getVolunteerList($volunteerIds, $onlyEnabled);

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
            $this->userManager->findForCurrentUser(),
            $volunteerIds
        );
    }

    public function searchInStructureQueryBuilder(Structure $structure,
        ?string $criteria,
        bool $onlyEnabled,
        bool $onlyUsers)
    {
        return $this->volunteerRepository->searchInStructureQueryBuilder($structure, $criteria, $onlyEnabled, $onlyUsers);
    }

    public function searchAllQueryBuilder(?string $criteria, bool $onlyEnabled, bool $onlyUsers) : QueryBuilder
    {
        return $this->volunteerRepository->searchAllWithFiltersQueryBuilder($criteria, $onlyEnabled, $onlyUsers);
    }

    public function searchForCurrentUserQueryBuilder(?string $criteria,
        bool $onlyEnabled,
        bool $onlyUsers) : QueryBuilder
    {
        return $this->volunteerRepository->searchForUserQueryBuilder(
            $this->userManager->findForCurrentUser(),
            $criteria,
            $onlyEnabled,
            $onlyUsers
        );
    }

    public function foreach(callable $callback, bool $onlyEnabled = true)
    {
        $this->volunteerRepository->foreach($callback, $onlyEnabled);
    }

    public function findIssues() : array
    {
        $volunteers = $this->volunteerRepository->getIssues(
            $this->userManager->findForCurrentUser()
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

    public function getIssues() : array
    {
        return $this->volunteerRepository->getIssues(
            $this->userManager->findForCurrentUser()
        );
    }

    public function synchronizeWithPegass()
    {
        $this->volunteerRepository->synchronizeWithPegass();
    }

    public function getIdsByNivols(array $nivols) : array
    {
        return array_column(
            $this->volunteerRepository->getIdsByNivols(
                array_map(function ($nivol) {
                    return ltrim($nivol, '0');
                }, $nivols)
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

    public function getVolunteerGlobalCounts(array $structureIds) : int
    {
        return $this->volunteerRepository->getVolunteerGlobalCounts($structureIds);
    }

    public function filterInvalidNivols(array $nivols) : array
    {
        return $this->volunteerRepository->filterInvalidNivols(
            array_map(function ($nivol) {
                return ltrim($nivol, '0');
            }, $nivols)
        );
    }

    public function filterInaccessibles(array $volunteerIds) : array
    {
        return $this->volunteerRepository->filterInaccessibles(
            $this->userManager->findForCurrentUser(),
            $volunteerIds
        );
    }

    public function filterDisabled(array $volunteerIds) : array
    {
        return array_column($this->volunteerRepository->filterDisabled($volunteerIds), 'id');
    }

    public function filterOptoutUntil(array $volunteerIds) : array
    {
        return array_column($this->volunteerRepository->filterOptoutUntil($volunteerIds), 'id');
    }

    public function filterPhoneLandline(array $volunteerIds) : array
    {
        return array_column($this->volunteerRepository->filterPhoneLandline($volunteerIds), 'id');
    }

    public function filterPhoneMissing(array $volunteerIds) : array
    {
        return array_column($this->volunteerRepository->filterPhoneMissing($volunteerIds), 'id');
    }

    public function filterPhoneOptout(array $volunteerIds) : array
    {
        return array_column($this->volunteerRepository->filterPhoneOptout($volunteerIds), 'id');
    }

    public function filterEmailMissing(array $volunteerIds) : array
    {
        return array_column($this->volunteerRepository->filterEmailMissing($volunteerIds), 'id');
    }

    public function filterEmailOptout(array $volunteerIds) : array
    {
        return array_column($this->volunteerRepository->filterEmailOptout($volunteerIds), 'id');
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

    public function reactivateTemporaryOptouts()
    {
        $this->volunteerRepository->reactivateTemporaryOptouts();
    }
}