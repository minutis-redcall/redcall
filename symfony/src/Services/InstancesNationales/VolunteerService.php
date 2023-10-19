<?php

namespace App\Services\InstancesNationales;

use App\Command\AnnuaireNationalCommand;
use App\Entity\Phone;
use App\Entity\Volunteer;
use App\Enum\Platform;
use App\Manager\StructureManager;
use App\Manager\VolunteerManager;
use App\Model\InstancesNationales\SheetExtract;
use App\Model\InstancesNationales\SheetsExtract;
use App\Model\InstancesNationales\VolunteerExtract;
use App\Model\InstancesNationales\VolunteersExtract;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class VolunteerService
{
    const ANNUAIRE = 'Annuaire_opé';
    const LISTES   = 'Viappel - Listes';

    /**
     * @var StructureManager
     */
    private $structureManager;

    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    public function __construct(StructureManager $structureManager, VolunteerManager $volunteerManager)
    {
        $this->structureManager = $structureManager;
        $this->volunteerManager = $volunteerManager;
    }

    public function extractVolunteersFromGSheets() : SheetsExtract
    {
        $id = getenv('GOOGLE_SHEETS_ANNUAIRE_NATIONAL_ID');

        LogService::info('Downloading Google Sheet', [
            'id' => $id,
        ]);

        $client = new \Google_Client();
        $client->setScopes([
            \Google_Service_Sheets::SPREADSHEETS_READONLY,
        ]);
        $client->useApplicationDefaultCredentials();

        $sheets = new \Google_Service_Sheets($client);

        // -----------------------------------------------

        LogService::info('Downloading tab', [
            'id'  => $id,
            'tab' => self::ANNUAIRE,
        ]);

        $extracts = new SheetsExtract();
        $extracts->addTab(
            SheetExtract::fromRows(
                self::ANNUAIRE,
                0,
                $sheets
                    ->spreadsheets_values
                    ->get($id, self::ANNUAIRE)
                    ->getValues()
            )
        );

        // -----------------------------------------------

        LogService::info('Downloading tab', [
            'id'  => $id,
            'tab' => self::LISTES,
        ]);

        $extracts->addTab(
            SheetExtract::fromRows(
                self::LISTES,
                1,
                $sheets
                    ->spreadsheets_values
                    ->get($id, self::LISTES)
                    ->getValues()
            )
        );

        // -----------------------------------------------

        LogService::pass('Download complete', [
            'id'            => $id,
            'rows_annuaire' => $extracts->getTab(self::ANNUAIRE)->count(),
            'rows_listes'   => $extracts->getTab(self::LISTES)->count(),
        ]);

        return $extracts;
    }

    public function extractVolunteers(SheetsExtract $extract) : VolunteersExtract
    {
        $tab = $extract->getTab(self::ANNUAIRE);

        $volunteers = $this->extractVolunteersFromSheet($tab);

        $this->filterVolunteers($volunteers, $extract->getTab(self::LISTES));

        $this->deleteMissingVolunteers($volunteers);
        $this->crupdateVolunteers($volunteers);

        return $volunteers;
    }

    private function extractVolunteersFromSheet(SheetExtract $extract) : VolunteersExtract
    {
        LogService::info('Extracting "volunteer" entities from Google Sheets', [
            'rows' => count($extract->getRows()),
        ]);

        $volunteers = new VolunteersExtract();
        foreach ($extract->getRows() as $index => $row) {
            $id = $row['ID'];

            if (!$id) {
                continue;
            }

            $volunteer = new VolunteerExtract();
            $volunteer->setId($id);

            $volunteer->setFirstname($row['Nom'] ?? null);
            $volunteer->setLastname($row['Prénom'] ?? null);

            $this->populatePhone($volunteer, $row['Téléphone_1'], 'A', $index);
            $this->populatePhone($volunteer, $row['Téléphone_2'], 'B', $index);

            // Emails are rows K and L
            $this->populateEmail($volunteer, $row['Mail_1'], 'A', $index);
            $this->populateEmail($volunteer, $row['Mail_2'], 'B', $index);

            if ($volunteer->isEmpty()) {
                LogService::fail('No contact info', [
                    'id'    => $volunteer->getId(),
                    'index' => $index,
                ]);

                continue;
            }

            $volunteers->addVolunteer($volunteer);
        }

        LogService::pass('Extracted "volunteer" entities from Google Sheets', [
            'rows' => $volunteers->count(),
        ]);

        return $volunteers;
    }

    private function filterVolunteers(VolunteersExtract $volunteers, SheetExtract $list)
    {
        LogService::info('Filtering out non-active "volunteer" entities', [
            'rows' => $volunteers->count(),
        ]);

        foreach ($volunteers->getVolunteers() as $volunteer) {
            $row = $list->getRow([
                'Clé' => $volunteer->getId(),
            ]);

            // "Active" is column B
            if ('O' !== $row['Actif']) {
                $volunteers->remove($volunteer);
            }
        }

        LogService::pass('Filtered out non-active "volunteer" entities', [
            'rows' => $volunteers->count(),
        ]);
    }

    private function deleteMissingVolunteers(VolunteersExtract $extract) : void
    {
        $structure = $this->structureManager->findOneByName(Platform::FR, AnnuaireNationalCommand::STRUCTURE_NAME);

        $inExtract = array_map(function (VolunteerExtract $volunteer) {
            return $volunteer->getNivol();
        }, $extract->getVolunteers());

        $inStructure = array_map(function (Volunteer $volunteer) {
            return $volunteer->getExternalId();
        }, $structure->getVolunteers()->toArray());

        $toDelete = array_diff($inStructure, $inExtract);

        foreach ($toDelete as $nivol) {
            LogService::pass('Deleting a "volunteer" entity existing in RedCall but missing in sheets', [
                'nivol' => $nivol,
            ], true);

            $volunteer = $structure->getVolunteer($nivol);
            $volunteer->setEnabled(false);

            $structure->removeVolunteer($volunteer);
        }
    }

    private function crupdateVolunteers(VolunteersExtract $extract) : void
    {
        $structure = $this->structureManager->findOneByName(Platform::FR, AnnuaireNationalCommand::STRUCTURE_NAME);

        foreach ($extract->getVolunteers() as $fromExtract) {
            $changes = false;
            $nivol   = $fromExtract->getNivol();

            if (null === $fromDatabase = $this->volunteerManager->findOneByExternalId(Platform::FR, $nivol)) {
                $changes = true;

                LogService::pass('Create a "volunteer" entity existing in sheets but missing in RedCall', [
                    'nivol' => $nivol,
                ], true);

                $fromDatabase = new Volunteer();
                $fromDatabase->setPlatform(Platform::FR);
                $fromDatabase->setExternalId($nivol);
                $structure->addVolunteer($fromDatabase);
            }

            if ($fromDatabase->getFirstName() !== $fromExtract->getFirstname()) {
                $changes = true;

                LogService::pass('Update a "volunteer" entity (first name)', [
                    'nivol' => $nivol,
                    'from'  => $fromDatabase->getFirstName(),
                    'to'    => $fromExtract->getFirstname(),
                ], true);

                $fromDatabase->setFirstName($fromExtract->getFirstname());
            }

            if ($fromDatabase->getLastName() !== $fromExtract->getLastname()) {
                $changes = true;

                LogService::pass('Update a "volunteer" entity (last name)', [
                    'nivol' => $nivol,
                    'from'  => $fromDatabase->getLastName(),
                    'to'    => $fromExtract->getLastname(),
                ], true);

                $fromDatabase->setLastName($fromExtract->getLastname());
            }

            if ($fromDatabase->getEmail() !== $fromExtract->getEmail()) {
                $changes = true;

                LogService::pass('Update a "volunteer" entity (email)', [
                    'nivol' => $nivol,
                    'from'  => $fromDatabase->getEmail(),
                    'to'    => $fromExtract->getEmail(),
                ], true);

                $fromDatabase->setEmail($fromExtract->getEmail());
            }

            if ($fromDatabase->getPhone() && !$fromExtract->getPhone()) {
                $changes = true;

                LogService::pass('Update a "volunteer" entity (remove phone)', [
                    'nivol' => $nivol,
                    'from'  => $fromDatabase->getPhone()->getE164(),
                    'to'    => $fromExtract->getPhone(),
                ], true);

                $fromDatabase->clearPhones();
            } elseif (0 === $fromDatabase->getPhones()->count() && $fromExtract->getPhone()) {
                $changes = true;

                LogService::pass('Update a "volunteer" entity (add phone)', [
                    'nivol' => $nivol,
                    'from'  => null,
                    'to'    => $fromExtract->getPhone(),
                ], true);

                $phone = new Phone();
                $phone->setE164($fromExtract->getPhone());
                $phone->setPreferred(true);
                $fromDatabase->addPhone($phone);
            } elseif ($fromDatabase->getPhone()->getE164() !== $fromExtract->getPhone()) {
                $changes = true;

                LogService::pass('Update a "volunteer" entity (phone)', [
                    'nivol' => $nivol,
                    'from'  => $fromDatabase->getPhone()->getE164(),
                    'to'    => $fromExtract->getPhone(),
                ], true);

                $fromDatabase->getPhone()->setE164($fromExtract->getPhone());
            }

            if ($changes) {
                $this->volunteerManager->save($fromDatabase);
            }
        }

    }

    private function populatePhone(VolunteerExtract $extract, ?string $phoneNumber, string $letter, int $index) : void
    {
        if (empty($phoneNumber)) {
            return;
        }

        try {
            $phoneUtil = PhoneNumberUtil::getInstance();
            $parsed    = $phoneUtil->parse($phoneNumber, 'FR');
            $e164      = $phoneUtil->format($parsed, PhoneNumberFormat::E164);
        } catch (NumberParseException $e) {
            LogService::fail('Invalid phone', [
                'phone'     => $phoneNumber,
                'exception' => $e->getMessage(),
                'index'     => $index,
            ]);

            return;
        }

        if ('A' === $letter) {
            $extract->setPhoneA($e164);
        } else {
            $extract->setPhoneB($e164);
        }
    }

    private function populateEmail(VolunteerExtract $extract, ?string $email, string $letter, int $index) : void
    {
        if (empty($email)) {
            return;
        }

        $emails = explode(';', $email);
        foreach ($emails as $check) {
            if (false === filter_var($check, FILTER_VALIDATE_EMAIL)) {
                LogService::fail('Invalid email address', [
                    'email' => $check,
                    'index' => $index,
                ]);

                return;
            }
        }

        if ('A' === $letter) {
            $extract->setEmailA($email);
        } else {
            $extract->setEmailB($email);
        }
    }
}