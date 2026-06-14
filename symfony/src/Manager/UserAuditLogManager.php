<?php

namespace App\Manager;

use App\Entity\Structure;
use App\Entity\User;
use App\Entity\UserAuditLog;
use App\Enum\UserAuditAction;
use App\Repository\UserAuditLogRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * Writes append-only audit rows describing every sensitive change to a
 * RedCall User. Each call site captures an "old" snapshot, performs its
 * mutation, then invokes this manager — see the plan in
 * /Users/ninsuo/.claude/plans/generic-exploring-wren.md and the existing
 * messager ActivityManager for the conceptual model.
 */
class UserAuditLogManager
{
    /**
     * @var UserAuditLogRepository
     */
    private $userAuditLogRepository;

    public function __construct(UserAuditLogRepository $userAuditLogRepository)
    {
        $this->userAuditLogRepository = $userAuditLogRepository;
    }

    public function searchQueryBuilder(?string $criteria, bool $hideTechnical = false) : QueryBuilder
    {
        return $this->userAuditLogRepository->searchQueryBuilder($criteria, $hideTechnical);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildSnapshot(User $user) : array
    {
        $structures = [];
        foreach ($user->getStructures(false) as $structure) {
            /** @var Structure $structure */
            $structures[] = [
                'id'         => $structure->getId(),
                'externalId' => $structure->getExternalId(),
                'name'       => $structure->getName(),
            ];
        }
        usort($structures, function (array $a, array $b) {
            return strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        return [
            'username'         => $user->getUsername(),
            'externalId'       => $user->getExternalId(),
            'displayName'      => $user->getDisplayName(),
            'isVerified'       => (bool) $user->isVerified(),
            'isTrusted'        => (bool) $user->isTrusted(),
            'isAdmin'          => (bool) $user->isAdmin(),
            'isRoot'           => (bool) $user->isRoot(),
            'locked'           => (bool) $user->isLocked(),
            'structures'       => $structures,
            // 8-char prefix of sha256(password) — lets us detect password
            // rotation in the audit log without ever storing the hash itself.
            'passwordFingerprint' => substr(hash('sha256', (string) $user->getPassword()), 0, 8),
        ];
    }

    public function logCreated(?User $actor, ?string $cliLabel, User $target) : UserAuditLog
    {
        $snapshot = $this->buildSnapshot($target);

        return $this->write(UserAuditAction::CREATE(), $actor, $cliLabel, $target, $snapshot, $snapshot);
    }

    /**
     * @param array<string, mixed> $oldSnapshot
     */
    public function logUpdated(?User $actor, ?string $cliLabel, User $target, array $oldSnapshot) : ?UserAuditLog
    {
        $newSnapshot = $this->buildSnapshot($target);

        if ($oldSnapshot == $newSnapshot) {
            return null;
        }

        return $this->write(UserAuditAction::UPDATE(), $actor, $cliLabel, $target, [
            'old' => $oldSnapshot,
            'new' => $newSnapshot,
        ], $newSnapshot);
    }

    /**
     * @param array<string, mixed> $targetSnapshot a snapshot built BEFORE the target was removed
     */
    public function logDeleted(?User $actor, ?string $cliLabel, array $targetSnapshot) : UserAuditLog
    {
        $log = new UserAuditLog();
        $log->setAction(UserAuditAction::DELETE()->getValue());
        $log->setActor($actor);
        $log->setActorLabel($this->resolveActorLabel($actor, $cliLabel));
        $log->setTargetUser(null);
        $log->setTargetUsername(isset($targetSnapshot['username']) ? (string) $targetSnapshot['username'] : null);
        $log->setTargetExternalId(isset($targetSnapshot['externalId']) ? (string) $targetSnapshot['externalId'] : null);
        $log->setTargetDisplayName(isset($targetSnapshot['displayName']) ? (string) $targetSnapshot['displayName'] : null);
        $log->setSnapshot($targetSnapshot);

        $this->userAuditLogRepository->save($log);

        return $log;
    }

    /**
     * @param array<string, mixed> $snapshotPayload  the JSON we store on the row
     * @param array<string, mixed> $latestSnapshot   the most recent state of the target (used to denormalise lookup fields)
     */
    private function write(UserAuditAction $action,
        ?User $actor,
        ?string $cliLabel,
        User $target,
        array $snapshotPayload,
        array $latestSnapshot) : UserAuditLog
    {
        $log = new UserAuditLog();
        $log->setAction($action->getValue());
        $log->setActor($actor);
        $log->setActorLabel($this->resolveActorLabel($actor, $cliLabel));
        $log->setTargetUser($target);
        $log->setTargetUsername(isset($latestSnapshot['username']) ? (string) $latestSnapshot['username'] : $target->getUsername());
        $log->setTargetExternalId(isset($latestSnapshot['externalId']) ? (string) $latestSnapshot['externalId'] : $target->getExternalId());
        $log->setTargetDisplayName(isset($latestSnapshot['displayName']) ? (string) $latestSnapshot['displayName'] : $target->getDisplayName());
        $log->setSnapshot($snapshotPayload);

        $this->userAuditLogRepository->save($log);

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
