<?php

namespace App\Services\InstancesNationales;

use App\Command\AnnuaireNationalCommand;
use App\Entity\Structure;
use App\Entity\User;
use App\Manager\StructureManager;
use App\Manager\UserAuditLogManager;
use App\Manager\UserManager;
use App\Model\InstancesNationales\SheetExtract;
use App\Model\InstancesNationales\SheetsExtract;
use App\Model\InstancesNationales\UserExtract;
use App\Model\InstancesNationales\UsersExtract;

class UserService
{
    const WRITERS = 'Droits_modification';
    const READERS = 'Droits_lecture';
    const TABS    = [
        self::WRITERS,
        self::READERS,
    ];

    /**
     * @var StructureManager
     */
    private $structureManager;

    /**
     * @var UserManager
     */
    private $userManager;

    private UserAuditLogManager $userAuditLogManager;

    public function __construct(StructureManager $structureManager,
        UserManager $userManager,
        UserAuditLogManager $userAuditLogManager)
    {
        $this->structureManager    = $structureManager;
        $this->userManager         = $userManager;
        $this->userAuditLogManager = $userAuditLogManager;
    }

    public function extractUsers() : void
    {
        //        if (is_file('/tmp/listes.json')) {
        //            $extract = SheetsExtract::fromArray(json_decode(file_get_contents('/tmp/listes.json'), true));
        //        } else {
        $extract = $this->extractUsersFromGSheets();
        //            file_put_contents('/tmp/listes.json', json_encode($extract->toArray()));
        //        }

        $users = $this->extractObjectsFromGrid($extract);

        $structure = $this->structureManager->findOneByName(AnnuaireNationalCommand::STRUCTURE_NAME);
        $this->deleteMissingUsers($structure, $users);
        $this->createUsers($structure, $users);
    }

    private function extractUsersFromGSheets() : SheetsExtract
    {
        $id = getenv('GOOGLE_SHEETS_ANNUAIRE_DROITS_ID');

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

        $extracts = new SheetsExtract();
        foreach (self::TABS as $tab) {
            LogService::info('Downloading tab', [
                'id'  => $id,
                'tab' => $tab,
            ]);

            $extracts->addTab(
                SheetExtract::fromRows(
                    $tab,
                    0,
                    $sheets
                        ->spreadsheets_values
                        ->get($id, $tab)
                        ->getValues()
                )
            );
        }

        // -----------------------------------------------

        LogService::info('Download complete', [
            'id'           => $id,
            'rows_writers' => $extracts->getTab(self::WRITERS)->count(),
            'rows_readers' => $extracts->getTab(self::READERS)->count(),
        ]);

        return $extracts;
    }

    private function extractObjectsFromGrid(SheetsExtract $extract) : UsersExtract
    {
        LogService::info('Extracting "user" entities from Google Sheets', [
            'count_writers' => $extract->getTab(self::WRITERS)->count(),
            'count_readers' => $extract->getTab(self::READERS)->count(),
        ]);

        $rows = array_map('strtolower', array_filter(array_unique(array_merge(
            $extract->getTab(self::READERS)->getColumn('Email'),
            $extract->getTab(self::WRITERS)->getColumn('Email')
        ))));

        $users = new UsersExtract();
        foreach ($rows as $row) {
            if (false === filter_var($row, FILTER_VALIDATE_EMAIL)) {
                LogService::error('Invalid email address', [
                    'email' => $row,
                ]);

                continue;
            }

            $user = new UserExtract();
            $user->setEmail(strtolower($row));
            $users->addUser($user);
        }

        LogService::info('Extracted "user" entities from Google Sheets', [
            'count' => $users->count(),
        ]);

        return $users;
    }

    private function deleteMissingUsers(Structure $structure, UsersExtract $extract)
    {
        $fromExtracts = array_map(function (UserExtract $user) {
            return strtolower($user->getEmail());
        }, $extract->getUsers());

        $fromDatabases = array_map(function (User $user) {
            return strtolower($user->getUsername());
        }, $this->userManager->getRedCallUsersInStructure($structure, false));

        $toDeletes = array_diff($fromDatabases, $fromExtracts);

        foreach ($toDeletes as $toDelete) {
            $user = $this->userManager->findOneByUsername($toDelete);

            if (!$user || $user->isAdmin()) {
                continue;
            }

            LogService::success('deleted', 'Delete a user', [
                'email' => $toDelete,
            ]);

            $oldSnapshot = $this->userAuditLogManager->buildSnapshot($user);

            // The ONLY thing this sync may remove is the ANNUAIRE NATIONAL
            // structure itself. A user's NIVOL (external_id) and any other
            // (Pegass) structures are never touched here.
            $user->removeStructure($structure);

            // Hard discriminator for the dual-identity case (the same email
            // exists both in Pegass and in the Annuaire roster): the Annuaire
            // sync OWNS only pure email-keyed accounts, i.e. those with no
            // NIVOL. A user carrying an external_id is a Pegass / directory
            // operator — keep it, do NOT delete it, even if removing the
            // Annuaire structure left it with zero structures. Deleting such a
            // user (or, previously, relying on a volunteer lookup that could be
            // transiently empty) was a source of permission loss.
            if ($user->getExternalId()) {
                $this->userManager->save($user);
                $this->userAuditLogManager->logUpdated(
                    null,
                    sprintf('annuaire: kept directory user (NIVOL %s), removed only structure %s', $user->getExternalId(), $structure->getExternalId()),
                    $user,
                    $oldSnapshot
                );

                continue;
            }

            if ($user->getStructures()->count() === 0) {
                $this->userManager->remove($user);
                $this->userAuditLogManager->logDeleted(
                    null,
                    sprintf('annuaire: delete missing user (structure %s)', $structure->getExternalId()),
                    $oldSnapshot
                );
            } else {
                $this->userManager->save($user);
                $this->userAuditLogManager->logUpdated(
                    null,
                    sprintf('annuaire: remove structure %s', $structure->getExternalId()),
                    $user,
                    $oldSnapshot
                );
            }
        }
    }

    private function createUsers(Structure $structure, UsersExtract $extract)
    {
        foreach ($extract->getUsers() as $userExtract) {
            $user = $this->userManager->findOneByUsername(strtolower($userExtract->getEmail()));

            if (!$user || !$user->hasStructure($structure)) {
                LogService::success('new', 'Create a user', [
                    'email' => $userExtract->getEmail(),
                ]);

                $wasCreated = false;

                if (!$user) {
                    // Annuaire users are identified by their email. They are
                    // operators, not contactable directory people — no synthetic
                    // volunteer, and a NULL external id (no real NIVOL).
                    $user = new User();
                    $user->setLocale('fr');
                    $user->setTimezone('Europe/Paris');
                    $user->setUsername(strtolower($userExtract->getEmail()));
                    $user->setPassword('invalid hash');
                    $user->setIsVerified(true);
                    $user->setIsTrusted(true);

                    if (@preg_match('/(.*)\.(.*)@/', $userExtract->getEmail(), $matches)) {
                        $user->setFirstName(ucfirst($matches[1]));
                        $user->setLastName(ucfirst($matches[2]));
                    }

                    $wasCreated = true;
                }

                $oldSnapshot = $wasCreated ? null : $this->userAuditLogManager->buildSnapshot($user);
                $user->addStructure($structure);
                $this->userManager->save($user);

                if ($wasCreated) {
                    $this->userAuditLogManager->logCreated(
                        null,
                        sprintf('annuaire: create user (structure %s)', $structure->getExternalId()),
                        $user
                    );
                } else {
                    $this->userAuditLogManager->logUpdated(
                        null,
                        sprintf('annuaire: add structure %s', $structure->getExternalId()),
                        $user,
                        $oldSnapshot
                    );
                }
            }
        }
    }
}