<?php

namespace App\Services\InstancesNationales;

use App\Command\AnnuaireNationalCommand;
use App\Entity\Structure;
use App\Entity\User;
use App\Entity\Volunteer;
use App\Enum\Platform;
use App\Manager\StructureManager;
use App\Manager\UserManager;
use App\Manager\VolunteerManager;
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
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @var StructureManager
     */
    private $structureManager;

    /**
     * @var UserManager
     */
    private $userManager;

    public function __construct(VolunteerManager $volunteerManager,
        StructureManager $structureManager,
        UserManager $userManager)
    {
        $this->volunteerManager = $volunteerManager;
        $this->structureManager = $structureManager;
        $this->userManager      = $userManager;
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

        $structure = $this->structureManager->findOneByName(Platform::FR, AnnuaireNationalCommand::STRUCTURE_NAME);
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

        LogService::pass('Download complete', [
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
                LogService::fail('Invalid email address', [
                    'email' => $row,
                ]);

                continue;
            }

            $user = new UserExtract();
            $user->setEmail($row);
            $users->addUser($user);
        }

        LogService::pass('Extracted "user" entities from Google Sheets', [
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
        }, $this->userManager->getRedCallUsersInStructure($structure));

        $toDeletes = array_diff($fromDatabases, $fromExtracts);

        foreach ($toDeletes as $toDelete) {
            $user = $this->userManager->findOneByUsernameAndPlatform(Platform::FR, $toDelete);

            if (!$user || $user->isAdmin()) {
                continue;
            }

            LogService::pass('Delete a user', [
                'email' => $toDelete,
            ], true);

            $user->removeStructure($structure);

            if ($volunteer = $user->getVolunteer()) {
                if (substr($volunteer->getExternalId(), 0, strlen(UserExtract::NIVOL_PREFIX)) === UserExtract::NIVOL_PREFIX) {
                    $volunteer->setEnabled(false);
                    $this->volunteerManager->save($volunteer);
                }
            }

            if ($user->getStructures()->count() === 0) {
                $this->userManager->remove($user);
            } else {
                $this->userManager->save($user);
            }
        }
    }

    private function createUsers(Structure $structure, UsersExtract $extract)
    {
        foreach ($extract->getUsers() as $userExtract) {
            $user = $this->userManager->findOneByUsernameAndPlatform(Platform::FR, strtolower($userExtract->getEmail()));

            if (!$user || !$user->hasStructure($structure)) {
                LogService::pass('Create a user', [
                    'email' => $userExtract->getEmail(),
                ], true);

                if (!$user) {
                    $volunteer = $this->volunteerManager->findOneByExternalId(Platform::FR, $userExtract->getNivol());
                    if ($volunteer) {
                        $volunteer->setEnabled(true);
                    } else {
                        $volunteer = new Volunteer();
                        $volunteer->setPlatform(Platform::FR);
                        $volunteer->setExternalId($userExtract->getNivol());
                        $volunteer->setEmail($userExtract->getEmail());
                        $volunteer->setInternalEmail(strtolower($userExtract->getEmail()));

                        if (@preg_match('/(.*)\.(.*)@/', $userExtract->getEmail(), $matches)) {
                            $volunteer->setFirstName(ucfirst($matches[1]));
                            $volunteer->setLastName(ucfirst($matches[2]));
                        }

                        $this->volunteerManager->save($volunteer);
                    }

                    $user = new User();
                    $user->setPlatform(Platform::FR);
                    $user->setLocale('fr');
                    $user->setTimezone('Europe/Paris');
                    $user->setUsername(strtolower($userExtract->getEmail()));
                    $user->setPassword('invalid hash');
                    $user->setIsVerified(true);
                    $user->setIsTrusted(true);
                    $user->setVolunteer($volunteer);
                }

                $user->addStructure($structure);
                $this->userManager->save($user);
            }
        }
    }
}