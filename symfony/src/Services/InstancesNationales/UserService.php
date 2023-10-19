<?php

namespace App\Services\InstancesNationales;

use App\Model\InstancesNationales\SheetExtract;
use App\Model\InstancesNationales\SheetsExtract;
use App\Model\InstancesNationales\UserExtract;
use App\Model\InstancesNationales\UsersExtract;

class UserService
{
    const WRITERS = 'Droits_modification';
    const READERS = 'Droits_lecture';

    const TABS = [
        self::WRITERS,
        self::READERS,
    ];

    public function extractUsers() : void
    {
        $extract = $this->extractUsersFromGSheets();

        LogService::info('Extracting "user" entities from Google Sheets', [
            'count_writers' => $extract->getTab(self::WRITERS)->count(),
            'count_readers' => $extract->getTab(self::READERS)->count(),
        ]);

        $rows = array_filter(array_unique(array_merge(
            $extract->getTab(self::READERS)->getColumn('Email'),
            $extract->getTab(self::WRITERS)->getColumn('Email')
        )));

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
}