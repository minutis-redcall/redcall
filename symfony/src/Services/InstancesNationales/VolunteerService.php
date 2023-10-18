<?php

namespace App\Services\InstancesNationales;

use App\Model\InstancesNationales\SheetExtract;
use App\Model\InstancesNationales\SheetsExtract;
use App\Model\InstancesNationales\VolunteerExtract;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class VolunteerService
{
    const ANNUAIRE = 'Annuaire_opé';
    const LISTES   = 'Viappel - Listes';

    // Same order as in the Google Sheets
    const TABS = [
        self::ANNUAIRE,
        self::LISTES,
    ];

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

        $extracts = new SheetsExtract();
        foreach (self::TABS as $tab) {
            $extract = new SheetExtract();
            $extract->setIdentifier($tab);

            LogService::info('Downloading tab', [
                'id'  => $id,
                'tab' => $tab,
            ]);

            $extract->addRows(
                $sheets
                    ->spreadsheets_values
                    ->get($id, $tab)
                    ->getValues()
            );

            $extracts->addTab($extract);
        }

        LogService::pass('Downloaded Google Sheet', [
            'id' => $id,
        ]);

        return $extracts;
    }

    public function extractVolunteers(SheetExtract $extract) : array
    {
        LogService::info('Extracting "volunteer" entities from Google Sheets', [
            'rows' => count($extract->getRows()),
        ]);

        $volunteers = [];
        foreach ($extract->getRows() as $index => $row) {
            $id = $row[0];

            if (!$id) {
                continue;
            }

            $volunteer = new VolunteerExtract();
            $volunteer->setId($id);

            // Phones are rows I and J
            $this->populatePhone($volunteer, $row[8], 'A', $index);
            $this->populatePhone($volunteer, $row[9], 'B', $index);

            // Emails are rows K and L
            $this->populateEmail($volunteer, $row[10], 'A');
            $this->populateEmail($volunteer, $row[11], 'B');

            $volunteers[] = $volunteer;
        }

        LogService::pass('Extracted "volunteer" entities from Google Sheets', [
            'rows' => count($volunteers),
        ]);

        return $volunteers;
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
            LogService::fail('A phone number is invalid', [
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

    private function populateEmail(VolunteerExtract $extract, ?string $email, string $letter) : void
    {
        if (empty($email)) {
            return;
        }

        $emails = explode(';', $email);
        foreach ($emails as $check) {
            if (false === filter_var($check, FILTER_VALIDATE_EMAIL)) {
                LogService::fail('Invalid email address', [
                    'email' => $check,
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