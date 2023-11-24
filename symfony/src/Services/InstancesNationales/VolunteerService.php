<?php

namespace App\Services\InstancesNationales;

use App\Command\AnnuaireNationalCommand;
use App\Entity\Phone;
use App\Entity\Structure;
use App\Entity\Volunteer;
use App\Entity\VolunteerList;
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

    public function extractVolunteers()
    {
        //        if (is_file('/tmp/annuaire.json')) {
        //            $extract = SheetsExtract::fromArray(json_decode(file_get_contents('/tmp/annuaire.json'), true));
        //        } else {
        $extract = $this->extractVolunteersFromGSheets();
        //            file_put_contents('/tmp/annuaire.json', json_encode($extract->toArray()));
        //        }

        $volunteers = $this->extractObjectsFromGrid($extract->getTab(self::ANNUAIRE));
        $this->filterVolunteers($volunteers, $extract->getTab(self::LISTES));

        $structure = $this->structureManager->findOneByName(Platform::FR, AnnuaireNationalCommand::STRUCTURE_NAME);
        if (null === $structure) {
            $structure = new Structure();
            $structure->setExternalId('NATIONAL');
            $structure->setPlatform(Platform::FR());
            $structure->setName(AnnuaireNationalCommand::STRUCTURE_NAME);
            $structure->setShortcut('NATIONAL');
            $this->structureManager->save($structure);
        }

        $this->deleteMissingVolunteers($structure, $volunteers);
        $this->crupdateVolunteers($structure, $volunteers);

        $this->createLists($structure, $extract->getTab(self::LISTES));

        $this->structureManager->save($structure);
    }

    private function extractVolunteersFromGSheets() : SheetsExtract
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

        LogService::info('Download complete', [
            'id'            => $id,
            'rows_annuaire' => $extracts->getTab(self::ANNUAIRE)->count(),
            'rows_listes'   => $extracts->getTab(self::LISTES)->count(),
        ]);

        return $extracts;
    }

    private function extractObjectsFromGrid(SheetExtract $extract) : VolunteersExtract
    {
        LogService::info('Extracting volunteers from Google Sheets', [
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

        LogService::info('Extracted volunteers from Google Sheets', [
            'rows' => $volunteers->count(),
        ]);

        return $volunteers;
    }

    private function filterVolunteers(VolunteersExtract $volunteers, SheetExtract $list)
    {
        LogService::info('Filtering out non-active volunteers', [
            'rows' => $volunteers->count(),
        ]);

        foreach ($volunteers->getVolunteers() as $volunteer) {
            $row = $list->getRow([
                'Clé' => $volunteer->getId(),
            ]);

            // "Active" is column B
            if (!$row || 'O' !== $row['Actif']) {
                $volunteers->remove($volunteer);
            }
        }

        LogService::info('Filtered out non-active volunteers', [
            'rows' => $volunteers->count(),
        ]);
    }

    private function deleteMissingVolunteers(Structure $structure, VolunteersExtract $extract) : void
    {
        $inExtract = array_map(function (VolunteerExtract $volunteer) {
            return $volunteer->getNivol();
        }, $extract->getVolunteers());

        $inStructure = array_map(function (Volunteer $volunteer) {
            return $volunteer->getExternalId();
        }, $structure->getVolunteers()->toArray());

        $toDelete = array_diff($inStructure, $inExtract);

        foreach ($toDelete as $nivol) {
            $volunteer = $structure->getVolunteer($nivol);

            if (!$volunteer) {
                continue;
            }

            LogService::pass('Deleting a volunteer existing in RedCall but missing in sheets', [
                'nivol' => $nivol,
            ], true);

            $volunteer->setEnabled(false);

            $structure->removeVolunteer($volunteer);
            $this->volunteerManager->save($volunteer);
            $this->structureManager->save($structure);
        }
    }

    private function crupdateVolunteers(Structure $structure, VolunteersExtract $extract) : void
    {
        foreach ($extract->getVolunteers() as $fromExtract) {
            $changes = false;
            $nivol   = $fromExtract->getNivol();

            if (null === $fromDatabase = $this->volunteerManager->findOneByExternalId(Platform::FR, $nivol)) {
                $changes = true;

                LogService::pass('Create a volunteer existing in sheets but missing in RedCall', [
                    'nivol' => $nivol,
                ], true);

                $fromDatabase = new Volunteer();
                $fromDatabase->setPlatform(Platform::FR);
                $fromDatabase->setExternalId($nivol);
                $structure->addVolunteer($fromDatabase);
            }

            if ($fromDatabase->getFirstName() !== $fromExtract->getFirstname()) {
                $changes = true;

                LogService::pass('Update a volunteer (first name)', [
                    'nivol' => $nivol,
                    'from'  => $fromDatabase->getFirstName(),
                    'to'    => $fromExtract->getFirstname(),
                ], true);

                $fromDatabase->setFirstName($fromExtract->getFirstname());
            }

            if ($fromDatabase->getLastName() !== $fromExtract->getLastname()) {
                $changes = true;

                LogService::pass('Update a volunteer (last name)', [
                    'nivol' => $nivol,
                    'from'  => $fromDatabase->getLastName(),
                    'to'    => $fromExtract->getLastname(),
                ], true);

                $fromDatabase->setLastName($fromExtract->getLastname());
            }

            if ($fromDatabase->getEmail() !== $fromExtract->getEmail()) {
                $changes = true;

                LogService::pass('Update a volunteer (email)', [
                    'nivol' => $nivol,
                    'from'  => $fromDatabase->getEmail(),
                    'to'    => $fromExtract->getEmail(),
                ], true);

                $fromDatabase->setEmail($fromExtract->getEmail());
            }

            $from = null;
            if ($fromDatabase->getPhones()->count() > 0) {
                $from = $fromDatabase->getPhones()->first()->getE164();
            }
            $to = null;
            if ($fromExtract->getPhone()) {
                $to = $fromExtract->getPhone();
            }
            if ($from !== $to) {
                $changes = true;

                LogService::pass('Update a volunteer (phone)', [
                    'nivol' => $nivol,
                    'from'  => $from,
                    'to'    => $to,
                ], true);

                $fromDatabase->clearPhones();

                if ($to) {
                    $phone = new Phone();
                    $phone->setE164($to);
                    $phone->setPreferred(true);
                    $fromDatabase->addPhone($phone);
                }
            }

            if ($changes) {
                $this->volunteerManager->save($fromDatabase);
            }
        }
    }

    private function createLists(Structure $structure, SheetExtract $extract)
    {
        $fromDatabaseNames = array_map(function (VolunteerList $list) {
            return $list->getName();
        }, $structure->getVolunteerLists()->toArray());

        $fromExtractNames = [];
        $columnNames      = array_keys($extract->getRows()[0]);
        foreach ($columnNames as $columnName) {
            if (preg_match('/^\d\d\:/', $columnName)) {
                $fromExtractNames[] = $columnName;
            }
        }

        $toRemoves = array_diff($fromDatabaseNames, $fromExtractNames);
        foreach ($toRemoves as $toRemove) {
            LogService::pass('Deleting volunteer existing in RedCall but missing in sheets', [
                'name' => $toRemove,
            ], true);

            $list = $structure->getVolunteerList($toRemove);
            $structure->removeVolunteerList($list);
        }

        $toCreates = array_diff($fromExtractNames, $fromDatabaseNames);
        foreach ($toCreates as $toCreate) {
            LogService::pass('Creating volunteer existing in sheets but missing in RedCall', [
                'name' => $toCreate,
            ], true);

            $list = new VolunteerList();
            $list->setName($toCreate);
            $list->setAudience([]);
            $structure->addVolunteerList($list);
        }

        foreach ($fromExtractNames as $listName) {
            $list    = $structure->getVolunteerList($listName);
            $changes = false;

            foreach ($extract->getRows() as $row) {
                $nivol = VolunteerExtract::buildNivol($row['Clé']);

                if (!$volunteer = $structure->getVolunteer($nivol)) {
                    continue;
                }

                if ('O' === $row[$listName]) {
                    if (!$list->hasVolunteer($volunteer)) {
                        $changes = true;

                        LogService::pass('Add volunteer in list', [
                            'list'  => $listName,
                            'nivol' => $nivol,
                        ], true);

                        $list->addVolunteer($volunteer);
                    }
                } elseif ($list->hasVolunteer($volunteer)) {
                    $changes = true;

                    LogService::pass('Remove volunteer from list', [
                        'list'  => $listName,
                        'nivol' => $nivol,
                    ], true);

                    $list->removeVolunteer($volunteer);
                }
            }

            if ($changes) {
                $list->setAudience([
                    'volunteers' => array_map(function (Volunteer $volunteer) {
                        return $volunteer->getId();
                    }, $list->getVolunteers()->toArray()),
                ]);
            }
        }

        $this->structureManager->save($structure);
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