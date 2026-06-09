<?php

namespace App\Sync\Importer;

use App\Entity\Badge;
use App\Entity\Phone;
use App\Entity\Volunteer;
use App\Manager\PhoneManager;
use App\Manager\StructureManager;
use App\Manager\UserManager;
use App\Manager\VolunteerManager;
use App\Sync\Dto\ActionRow;
use App\Sync\Dto\NominationRow;
use App\Sync\Dto\SkillRow;
use App\Sync\Dto\TrainingRow;
use App\Sync\Dto\VolunteerRow;
use App\Sync\Writer\VolunteerSyncSnapshotWriter;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class VolunteerImporter
{
    private VolunteerManager $volunteerManager;
    private StructureManager $structureManager;
    private UserManager $userManager;
    private PhoneManager $phoneManager;
    private BadgeFactory $badgeFactory;
    private VolunteerSyncSnapshotWriter $snapshotWriter;
    private LoggerInterface $logger;

    public function __construct(
        VolunteerManager $volunteerManager,
        StructureManager $structureManager,
        UserManager $userManager,
        PhoneManager $phoneManager,
        BadgeFactory $badgeFactory,
        VolunteerSyncSnapshotWriter $snapshotWriter,
        ?LoggerInterface $logger = null
    ) {
        $this->volunteerManager = $volunteerManager;
        $this->structureManager = $structureManager;
        $this->userManager      = $userManager;
        $this->phoneManager     = $phoneManager;
        $this->badgeFactory     = $badgeFactory;
        $this->snapshotWriter   = $snapshotWriter;
        $this->logger           = $logger ?? new NullLogger();
    }

    public function import(VolunteerRow $row, ?\DateTimeImmutable $syncedAt = null) : void
    {
        $firstName = $this->normalizeName($row->firstName);
        $lastName  = $this->normalizeName($row->lastName);

        if ('' === $firstName || '' === $lastName) {
            $this->logger->notice(sprintf('Volunteer %s has empty name, skipping', $row->nivol));

            return;
        }

        $externalId = ltrim($row->nivol, '0');

        $volunteer = $this->volunteerManager->findOneByExternalId($externalId);
        if (!$volunteer) {
            $volunteer = new Volunteer();
        }

        $volunteer->setExternalId($externalId);
        $volunteer->setReport([]);
        $volunteer->setFirstName($firstName);
        $volunteer->setLastName($lastName);
        $volunteer->setMinor($row->isMinor());
        $volunteer->setLastSyncedAt(\DateTime::createFromImmutable($syncedAt ?? new \DateTimeImmutable()));

        $this->updateStructures($volunteer, $row);

        if ($volunteer->isLocked()) {
            $volunteer->addReport('import_report.update_locked');
            $volunteer->removeExpiredBadges();
            $this->volunteerManager->save($volunteer);
            $this->updateBoundUserStructures($volunteer);

            return;
        }

        $volunteer->setEnabled(true);

        $this->updateContact($volunteer, $row);
        $volunteer->setExternalBadges($this->buildBadges($row));

        $this->volunteerManager->save($volunteer);
        $this->updateBoundUserStructures($volunteer);
        $this->writeSnapshot($externalId, $row, $syncedAt ?? new \DateTimeImmutable());
    }

    private function writeSnapshot(string $externalId, VolunteerRow $row, \DateTimeImmutable $syncedAt) : void
    {
        // Buffered upsert via raw DBAL — flushed by the orchestrator at the
        // end of every chunk. Avoids the per-row SELECT + INSERT/UPDATE +
        // EM flush that previously doubled the round-trips against MySQL.
        $this->snapshotWriter->queue($externalId, $syncedAt, $row->toArray());
    }

    private function updateStructures(Volunteer $volunteer, VolunteerRow $row) : void
    {
        $primaryStructures = [];
        $seenIds           = [];

        if ('' !== $row->structureId) {
            $primary = $this->structureManager->findOneByExternalId($row->structureId);
            if ($primary) {
                $primaryStructures[] = $primary;
            }
            $seenIds[] = $row->structureId;
        }

        $volunteer->syncStructures($primaryStructures);

        foreach ($row->actions as $action) {
            /** @var ActionRow $action */
            if ('' === $action->structureId || in_array($action->structureId, $seenIds, true)) {
                continue;
            }

            $structure = $this->structureManager->findOneByExternalId($action->structureId);
            if ($structure) {
                $volunteer->addStructure($structure);
            }
            $seenIds[] = $action->structureId;
        }
    }

    private function updateContact(Volunteer $volunteer, VolunteerRow $row) : void
    {
        if ('' !== $row->organizationEmail) {
            $volunteer->setInternalEmail($row->organizationEmail);
        }

        if (!$volunteer->isEmailLocked()) {
            if ('' !== $row->personalEmail) {
                $volunteer->setEmail($row->personalEmail);
            } elseif ('' === ((string) $volunteer->getEmail()) && '' !== $row->organizationEmail) {
                $volunteer->setEmail($row->organizationEmail);
            }
        }

        if (!$volunteer->isPhoneNumberLocked() && '' !== $row->phone) {
            $this->updatePhone($volunteer, $row->phone);
        }
    }

    private function updatePhone(Volunteer $volunteer, string $phoneNumber) : void
    {
        $phoneNumber = str_replace('+330', '+33', $phoneNumber);

        if ($phoneNumber === $volunteer->getPhoneNumber()) {
            return;
        }

        $volunteer->clearPhones();

        $phoneUtil = PhoneNumberUtil::getInstance();
        try {
            /** @var PhoneNumber $parsed */
            $parsed = $phoneUtil->parse($phoneNumber, Phone::DEFAULT_LANG);
            $e164   = $phoneUtil->format($parsed, PhoneNumberFormat::E164);
            if (!$volunteer->hasPhoneNumber($e164)) {
                $phone = new Phone();
                $phone->setPreferred(true);
                $phone->setE164($e164);
                $volunteer->addPhone($phone);
            }
        } catch (NumberParseException $e) {
            // Bad phone number — silently keep the volunteer without phone
        }
    }

    /**
     * @return Badge[]
     */
    private function buildBadges(VolunteerRow $row) : array
    {
        $badges = [];

        foreach ($row->actions as $action) {
            /** @var ActionRow $action */
            if ('' === $action->groupActionId) {
                continue;
            }
            $badges[] = $this->badgeFactory->findOrCreate(
                sprintf('groupeAction-%s', $action->groupActionId),
                $action->groupActionLabel
            );
        }

        foreach ($row->skills as $skill) {
            /** @var SkillRow $skill */
            if ('' === $skill->competenceId) {
                continue;
            }
            $badges[] = $this->badgeFactory->findOrCreate(
                sprintf('skill-%s', $skill->competenceId),
                $skill->label
            );
        }

        $now = new \DateTimeImmutable();
        foreach ($row->trainings as $training) {
            /** @var TrainingRow $training */
            if ('' === $training->formationId) {
                continue;
            }
            if ($training->expiresAt !== null && $training->expiresAt < $now) {
                continue;
            }

            // The badge (and its expires_at) was already pre-created by
            // DataSyncOrchestrator::precreateBadges() in StartDataSyncTask,
            // before any chunk got dispatched. We only attach it here.
            // findOrCreate is kept as a safety net for the rare case where a
            // CSV row references an id that wasn't seen during the pre-pass.
            $badges[] = $this->badgeFactory->findOrCreate(
                sprintf('training-%s', $training->formationId),
                $training->code,
                $training->label
            );
        }

        foreach ($row->nominations as $nomination) {
            /** @var NominationRow $nomination */
            if ('' === $nomination->nominationId) {
                continue;
            }
            $badges[] = $this->badgeFactory->findOrCreate(
                sprintf('nomination-%s', $nomination->nominationId),
                $nomination->code,
                $nomination->label
            );
        }

        return $badges;
    }

    private function updateBoundUserStructures(Volunteer $volunteer) : void
    {
        $user = $this->userManager->findOneByExternalId($volunteer->getExternalId());
        if (!$user) {
            return;
        }
        $this->userManager->changeVolunteer($user, $volunteer->getExternalId());
    }

    private function normalizeName(string $name) : string
    {
        if ('' === $name) {
            return '';
        }

        return sprintf('%s%s',
            mb_strtoupper(mb_substr($name, 0, 1)),
            mb_strtolower(mb_substr($name, 1))
        );
    }
}
