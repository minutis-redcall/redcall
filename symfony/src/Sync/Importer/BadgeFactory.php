<?php

namespace App\Sync\Importer;

use App\Entity\Badge;
use App\Manager\BadgeManager;

/**
 * Finds or creates Badge entities by external id, applying the legacy
 * truncation rules. External id conventions are unchanged from the legacy
 * RefreshManager:
 *   - "groupeAction-{id}" for activity group badges
 *   - "skill-{id}" for competence badges
 *   - "training-{id}" for formation badges
 *   - "nomination-{id}" for nomination badges
 */
class BadgeFactory
{
    private BadgeManager $badgeManager;

    public function __construct(BadgeManager $badgeManager)
    {
        $this->badgeManager = $badgeManager;
    }

    public function findOrCreate(string $externalId, string $name, ?string $description = null) : Badge
    {
        $badge = $this->badgeManager->findOneByExternalId($externalId);
        if ($badge) {
            return $badge;
        }

        if (null === $description) {
            $description = $name;
        }

        $badge = new Badge();
        $badge->setExternalId($externalId);
        $badge->setName(substr($name, 0, 64));
        $badge->setDescription(substr($description, 0, 255));
        $this->badgeManager->save($badge);

        return $badge;
    }

    /**
     * Update a training badge's expiration and persist the change.
     * Required because Badge uses DEFERRED_EXPLICIT change tracking — a setter
     * call alone would not be picked up by the next flush.
     */
    public function setTrainingExpiration(Badge $badge, ?\DateTimeImmutable $expiresAt) : void
    {
        $current = $badge->getExpiresAt();

        // Badge.expiresAt is mapped as date_immutable (date only) — normalize.
        $next = $expiresAt !== null
            ? \DateTimeImmutable::createFromFormat('!Y-m-d', $expiresAt->format('Y-m-d'))
            : null;

        if ($current === null && $next === null) {
            return;
        }
        if ($current !== null && $next !== null && $current->format('Y-m-d') === $next->format('Y-m-d')) {
            return;
        }

        $badge->setExpiresAt($next);
        $this->badgeManager->save($badge);
    }
}
