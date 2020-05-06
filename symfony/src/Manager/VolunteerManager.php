<?php

namespace App\Manager;

use App\Entity\Structure;
use App\Entity\Tag;
use App\Entity\UserInformation;
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
     * @var UserInformationManager
     */
    private $userInformationManager;

    /**
     * @var TagManager
     */
    private $tagManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param VolunteerRepository    $volunteerRepository
     * @param UserInformationManager $userInformationManager
     * @param TagManager             $tagManager
     * @param TranslatorInterface    $translator
     */
    public function __construct(VolunteerRepository $volunteerRepository, UserInformationManager $userInformationManager, TagManager $tagManager, TranslatorInterface $translator)
    {
        $this->volunteerRepository = $volunteerRepository;
        $this->userInformationManager = $userInformationManager;
        $this->tagManager = $tagManager;
        $this->translator = $translator;
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

    public function searchForCurrentUser(?string $criteria, int $limit, bool $onlyEnabled = false)
    {
        return $this->volunteerRepository->searchForUser(
            $this->userInformationManager->findForCurrentUser(),
            $criteria,
            $limit,
            $onlyEnabled
        );
    }

    public function searchInStructureQueryBuilder(Structure $structure, ?string $criteria)
    {
        return $this->volunteerRepository->searchInStructureQueryBuilder($structure, $criteria);
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

    public function synchronizeWithPegass()
    {
        $this->volunteerRepository->synchronizeWithPegass();
    }

    /**
     * @param array $nivols
     *
     * @return Volunteer[]
     */
    public function filterByNivolAndAccess(array $nivols): array
    {
       $user = $this->userInformationManager->findForCurrentUser();

       if ($user->isAdmin()) {
           return $this->volunteerRepository->filterByNivols($nivols);
       }

        return $this->volunteerRepository->filterByNivolsAndAccess($nivols, $user);
    }

    /**
     * @param array $ids
     *
     * @return Volunteer[]
     */
    public function filterByIdAndAccess(array $ids): array
    {
        $user = $this->userInformationManager->findForCurrentUser();

        if ($user->isAdmin()) {
            return $this->volunteerRepository->filterByIds($ids);
        }

        return $this->volunteerRepository->filterByIdsAndAccess($ids, $user);
    }

    public function classifyNivols(array $nivols): array
    {
        $user = $this->userInformationManager->findForCurrentUser();

        $accessibles = array_map(function(Volunteer $volunteer) {
            return $volunteer->getNivol();
        }, $this->filterByNivolAndAccess($nivols));

        if ($user->isAdmin()) {
            $inaccessibles = [];
        } else {
            $all = array_map(function(Volunteer $volunteer) {
                return $volunteer->getNivol();
            }, $this->volunteerRepository->filterByNivols($nivols));

            $inaccessibles = array_diff($all, $accessibles);
        }

        return [
            'invalid' => $this->volunteerRepository->filterInvalidNivols($nivols),
            'disabled' => $this->volunteerRepository->filterDisabledNivols($nivols),
            'inaccessible' => $inaccessibles,
        ];
    }

    public function loadVolunteersAudience(Structure $structure, array $nivols): array
    {
        $rows = $this->volunteerRepository->loadVolunteersAudience($structure, $nivols);

        return $this->populateDatalist($rows);
    }

    public function searchVolunteersAudience(Structure $structure, string $criteria): array
    {
        $rows = $this->volunteerRepository->searchVolunteersAudience($structure, $criteria);

        return $this->populateDatalist($rows);
    }

    public function searchVolunteerAudienceByTag(Tag $tag, Structure $structure): array
    {
        return $this->volunteerRepository->searchVolunteerAudienceByTag($tag, $structure);
    }

    private function populateDatalist(array $rows) : array
    {
        $tags = $this->tagManager->findTagsForNivols(
            array_unique(array_column($rows, 'nivol'))
        );

        $mapped = [];
        foreach ($rows as $volunteer) {
            $volunteer['tags'] = [];
            $mapped[$volunteer['nivol']] = $volunteer;
        }

        foreach ($tags as $tag) {
            $mapped[$tag['nivol']]['tags'][] = $this->translator->trans(sprintf('tag.shortcuts.%s', $tag['label']));
        }

        foreach ($mapped as $nivol => $volunteer) {
            $mapped[$nivol]['tags'] = implode(', ', $volunteer['tags']);
        }

        return array_values($mapped);
    }
}