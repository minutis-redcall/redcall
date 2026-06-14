<?php

namespace App\Sync\Reconciliation;

use App\Command\AnnuaireNationalCommand;
use App\Entity\Volunteer;
use App\Manager\StructureManager;
use App\Manager\UserAuditLogManager;
use App\Manager\UserManager;
use App\Manager\VolunteerManager;

/**
 * Post-sync reconciliation of RedCall user privileges based on the RTMR badge.
 *
 * Rules:
 *   1) A volunteer that is disabled or marked as "deleted-" loses every
 *      Trusted/Admin privilege and gets stripped from every structure (except
 *      the Annuaire National structure, which is managed elsewhere).
 *   2) A volunteer with the legacy invalid "RTMR" badge (typo) loses admin
 *      and structures (same exception).
 *   3) A volunteer with the canonical "RT MR" badge must have a RedCall user.
 *      If they don't, one is created with the structures they can trigger.
 *      Admin flag is forced off (RTMRs are NOT admins).
 */
class RtmrReconciliator
{
    public const RTMR_BADGE         = 'RT MR';
    public const INVALID_RTMR_BADGE = 'RTMR';

    private VolunteerManager $volunteerManager;
    private UserManager $userManager;
    private StructureManager $structureManager;
    private UserAuditLogManager $userAuditLogManager;

    public function __construct(
        VolunteerManager $volunteerManager,
        UserManager $userManager,
        StructureManager $structureManager,
        UserAuditLogManager $userAuditLogManager
    ) {
        $this->volunteerManager    = $volunteerManager;
        $this->userManager         = $userManager;
        $this->structureManager    = $structureManager;
        $this->userAuditLogManager = $userAuditLogManager;
    }

    public function reconcile(Volunteer $volunteer) : void
    {
        $this->clearPrivilegesIfDecommissioned($volunteer);
        $this->stripInvalidRtmrAdminRights($volunteer);
        $this->ensureRtmrHasUser($volunteer);
    }

    private function clearPrivilegesIfDecommissioned(Volunteer $volunteer) : void
    {
        $user = $volunteer->getUser();
        if (!$user) {
            return;
        }

        $clear = !$volunteer->isEnabled()
            || 0 === strncmp((string) $volunteer->getExternalId(), 'deleted-', 8);

        if (!$clear) {
            return;
        }

        $old = $this->userAuditLogManager->buildSnapshot($user);
        $user->setIsTrusted(false);
        $user->setIsAdmin(false);
        foreach ($user->getStructures() as $structure) {
            if (AnnuaireNationalCommand::STRUCTURE_NAME === $structure->getShortcut()) {
                continue;
            }
            $user->removeStructure($structure);
        }
        $this->userManager->save($user);
        $this->userAuditLogManager->logUpdated(null, 'sync: rtmr (decommissioned)', $user, $old);
    }

    private function stripInvalidRtmrAdminRights(Volunteer $volunteer) : void
    {
        if ($volunteer->hasBadge(self::RTMR_BADGE)) {
            return;
        }
        if (!$volunteer->hasBadge(self::INVALID_RTMR_BADGE)) {
            return;
        }
        $user = $volunteer->getUser();
        if (!$user) {
            return;
        }

        $old = $this->userAuditLogManager->buildSnapshot($user);
        $user->setIsAdmin(false);
        foreach ($user->getStructures() as $structure) {
            if (AnnuaireNationalCommand::STRUCTURE_NAME === $structure->getShortcut()) {
                continue;
            }
            $user->removeStructure($structure);
        }
        $this->userManager->save($user);
        $this->userAuditLogManager->logUpdated(null, 'sync: rtmr (invalid badge)', $user, $old);
    }

    private function ensureRtmrHasUser(Volunteer $volunteer) : void
    {
        if (!$volunteer->hasBadge(self::RTMR_BADGE)) {
            return;
        }

        $user = $volunteer->getUser();
        if (!$user) {
            $this->volunteerManager->save($volunteer);
            try {
                // createUser logs its own "create" audit row with the given label
                $user = $this->userManager->createUser($volunteer->getExternalId(), null, 'sync: rtmr (create user)');
            } catch (\LogicException $e) {
                return;
            }
            $old        = $this->userAuditLogManager->buildSnapshot($user);
            $structures = $this->structureManager->findCallableStructuresForVolunteer($volunteer);
            $user->updateStructures($structures);
            $this->userManager->save($user);
            $this->userAuditLogManager->logUpdated(null, 'sync: rtmr (initial structures)', $user, $old);
        }

        if ($user->isAdmin()) {
            $old = $this->userAuditLogManager->buildSnapshot($user);
            $user->setIsAdmin(false);
            $this->userManager->save($user);
            $this->userAuditLogManager->logUpdated(null, 'sync: rtmr (clear admin)', $user, $old);
        }
    }
}
