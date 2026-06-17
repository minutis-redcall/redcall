<?php

namespace App\Manager;

use App\Entity\Structure;
use App\Entity\User;
use App\Entity\Volunteer;
use App\Entity\VolunteerAuditLog;
use App\Enum\VolunteerAuditAction;
use App\Repository\UserRepository;
use App\Repository\VolunteerAuditLogRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * Writes append-only audit rows describing anonymize events on a Volunteer.
 *
 * Mirrors {@see UserAuditLogManager} but operates on a deliberately
 * narrower surface: anonymize only, and PII-free snapshots only. Used at
 * every call site of {@see VolunteerManager::anonymize()} so we can trace
 * "who killed which volunteer and why" after the fact.
 */
class VolunteerAuditLogManager
{
    /**
     * @var VolunteerAuditLogRepository
     */
    private $volunteerAuditLogRepository;

    /**
     * @var UserRepository
     */
    private $userRepository;

    public function __construct(VolunteerAuditLogRepository $volunteerAuditLogRepository,
        UserRepository $userRepository)
    {
        $this->volunteerAuditLogRepository = $volunteerAuditLogRepository;
        $this->userRepository              = $userRepository;
    }

    public function searchQueryBuilder(?string $criteria, bool $hideTechnical = false) : QueryBuilder
    {
        return $this->volunteerAuditLogRepository->searchQueryBuilder($criteria, $hideTechnical);
    }

    /**
     * Returns a strictly PII-free structural snapshot of the volunteer.
     * Caller is expected to invoke this BEFORE the destructive anonymize
     * runs, then pass the result to {@see logAnonymized}.
     *
     * @return array<string, mixed>
     */
    public function buildSnapshot(Volunteer $volunteer) : array
    {
        $structures = [];
        foreach ($volunteer->getStructures(false) as $structure) {
            /** @var Structure $structure */
            $externalId = $structure->getExternalId();
            if (null !== $externalId && '' !== $externalId) {
                $structures[] = $externalId;
            }
        }
        sort($structures);

        $badges = [];
        foreach ($volunteer->getBadges() as $badge) {
            $externalId = $badge->getExternalId();
            if (null !== $externalId && '' !== $externalId) {
                $badges[] = $externalId;
            }
        }
        sort($badges);

        $boundUser    = $this->userRepository->findOneTrustedByExternalId($volunteer->getExternalId());
        $lastSyncedAt = $volunteer->getLastSyncedAt();

        return [
            // The NIVOL is captured here on purpose: by the time logAnonymized
            // runs, DeletedVolunteerManager::anonymize has already renamed the
            // volunteer's external_id to `deleted-XXX`. The snapshot is the
            // only place that still carries the real NIVOL.
            'externalId'   => $volunteer->getExternalId(),
            'isLocked'     => (bool) $volunteer->isLocked(),
            'isEnabled'    => (bool) $volunteer->isEnabled(),
            'isMinor'      => (bool) $volunteer->isMinor(),
            'hadBoundUser' => null !== $boundUser,
            'structures'   => $structures,
            'badges'       => $badges,
            'lastSyncedAt' => $lastSyncedAt instanceof \DateTimeInterface ? $lastSyncedAt->format(\DateTimeInterface::ATOM) : null,
        ];
    }

    /**
     * @param array<string, mixed> $snapshot snapshot built by {@see buildSnapshot} BEFORE the anonymize ran
     */
    public function logAnonymized(?User $actor,
        ?string $cliLabel,
        Volunteer $target,
        array $snapshot,
        ?User $boundUser = null) : VolunteerAuditLog
    {
        $log = new VolunteerAuditLog();
        $log->setAction(VolunteerAuditAction::ANONYMIZE()->getValue());
        $log->setActor($actor);
        $log->setActorLabel($this->resolveActorLabel($actor, $cliLabel));
        $log->setTargetVolunteer($target);
        // Pre-anonymize NIVOL captured in the snapshot — the volunteer's own
        // external_id is `deleted-XXX` at this point.
        $log->setTargetExternalId(isset($snapshot['externalId']) ? (string) $snapshot['externalId'] : $target->getExternalId());
        $log->setTargetBoundUserId($boundUser ? $boundUser->getId() : null);
        $log->setSnapshot($snapshot);

        $this->volunteerAuditLogRepository->save($log);

        return $log;
    }

    private function resolveActorLabel(?User $actor, ?string $cliLabel) : string
    {
        if ($actor instanceof User) {
            $label = $actor->getDisplayName();
            if ('' !== (string) $label) {
                return $this->truncate($label, 64);
            }
        }

        if (null !== $cliLabel && '' !== $cliLabel) {
            return $this->truncate($cliLabel, 64);
        }

        return 'system';
    }

    private function truncate(string $value, int $length) : string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $length);
        }

        return substr($value, 0, $length);
    }
}
