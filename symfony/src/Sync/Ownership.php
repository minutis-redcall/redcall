<?php

namespace App\Sync;

use App\Entity\Structure;
use App\Entity\Volunteer;
use App\Model\InstancesNationales\UserExtract;
use App\Model\InstancesNationales\VolunteerExtract;

/**
 * Single source of truth for which entities belong to which sync.
 *
 * The Pegass sync (sync:data) and the Annuaire National sync
 * (import:national) must never mutate each other's rows. Volunteers
 * created by the Annuaire flow carry one of two synthetic prefixes
 * (see UserExtract / VolunteerExtract) and never appear in the Pegass
 * CSV — without this guard, the Pegass finalize phase anonymized them
 * for being "stale" and cascaded into hard-deleting their RedCall users.
 *
 * Conventions:
 *   - "Pegass-managed"   = external_id matching a real NIVOL pattern.
 *                          Volunteers: not one of the synthetic prefixes
 *                          AND not yet anonymized. Structures: purely
 *                          numeric (the CRF directory uses ints; the
 *                          handful of non-Pegass structures we have all
 *                          use UUIDs — ANNUAIRE NATIONAL, demos, etc.).
 *   - "Annuaire-managed" = external_id starts with one of the two
 *                          synthetic prefixes from InstancesNationales.
 *   - "Deleted"          = external_id starts with "deleted-".
 *                          Anonymized volunteer; off-limits for both syncs.
 */
final class Ownership
{
    public const DELETED_PREFIX = 'deleted-';

    /**
     * Synthetic prefixes used by the Annuaire National sync when it has
     * to materialise a Volunteer for a person it only knows via Google
     * Sheets (no real NIVOL).
     *
     * @var string[]
     */
    public const ANNUAIRE_VOLUNTEER_PREFIXES = [
        UserExtract::NIVOL_PREFIX,      // "user-annu-"
        VolunteerExtract::NIVOL_PREFIX, // "annuaire-"
    ];

    private function __construct()
    {
    }

    public static function isPegassVolunteer(?string $externalId) : bool
    {
        if ($externalId === null || $externalId === '') {
            return false;
        }
        if (self::startsWithAny($externalId, self::ANNUAIRE_VOLUNTEER_PREFIXES)) {
            return false;
        }
        if (str_starts_with($externalId, self::DELETED_PREFIX)) {
            return false;
        }

        return true;
    }

    public static function isAnnuaireVolunteer(?string $externalId) : bool
    {
        if ($externalId === null || $externalId === '') {
            return false;
        }

        return self::startsWithAny($externalId, self::ANNUAIRE_VOLUNTEER_PREFIXES);
    }

    /**
     * A Pegass-managed structure has a purely numeric external_id (the CRF
     * directory uses ints). Everything else — UUIDs from Annuaire National,
     * demos, etc. — is off-limits.
     */
    public static function isPegassStructure(?string $externalId) : bool
    {
        return $externalId !== null && $externalId !== '' && ctype_digit($externalId);
    }

    public static function isPegassVolunteerEntity(Volunteer $volunteer) : bool
    {
        return self::isPegassVolunteer((string) $volunteer->getExternalId());
    }

    public static function isPegassStructureEntity(Structure $structure) : bool
    {
        return self::isPegassStructure((string) $structure->getExternalId());
    }

    /**
     * @param string[] $prefixes
     */
    private static function startsWithAny(string $needle, array $prefixes) : bool
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($needle, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
