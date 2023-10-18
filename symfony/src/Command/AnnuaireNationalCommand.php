<?php

namespace App\Command;

use App\Manager\VolunteerManager;
use App\Model\Sheets\SheetExtract;
use App\Model\Sheets\SheetsExtract;
use App\Model\Sheets\VolunteerExtract;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The French red cross national entities manage a few list of key people
 * (volunteers, staff, etc.) in Google Sheets. This command fetches these
 * lists and import them into the database.
 *
 * The command code is in French because the Google Sheets are in French.
 */
class AnnuaireNationalCommand extends Command
{
    const ANNUAIRE = 'annuaire';
    const LISTES   = 'listes';

    // Same order as in the Google Sheets
    const TABS = [
        self::ANNUAIRE,
        self::LISTES,
    ];

    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    public function __construct(VolunteerManager $volunteerManager)
    {
        parent::__construct();

        $this->volunteerManager = $volunteerManager;
    }

    protected function configure()
    {
        parent::configure();

        $this->setName('annuaire-national')
             ->setDescription('Importe les listes de l\'annuaire national');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        if (is_file('/tmp/annuaire.json')) {
            $extract = SheetsExtract::fromArray(json_decode(file_get_contents('/tmp/annuaire.json'), true));
        } else {
            $extract = $this->extractRelevantTabs();
            file_put_contents('/tmp/annuaire.json', json_encode($extract->toArray()));
        }

        $volunteers = $this->extractVolunteers($extract->getTab(self::ANNUAIRE));

        // TODO step 1, correlate "id" (first row in the Google Sheets) with the volunteer we have in our db
        // TODO step 2, correlate lists (from columns I to Z) with the volunteer lists we have in DB for "instances nationales"
        // TODO step 3, combine lists extracted from step 2 with volunteers extracted from step 1

        return 0;
    }

    private function extractRelevantTabs() : SheetsExtract
    {
        $id = getenv('GOOGLE_SHEETS_ANNUAIRE_NATIONAL_ID');

        $client = new \Google_Client();
        $client->setScopes([
            \Google_Service_Sheets::SPREADSHEETS_READONLY,
        ]);
        $client->useApplicationDefaultCredentials();

        $sheets = new \Google_Service_Sheets($client);

        $extracts = new SheetsExtract();
        foreach (self::TABS as $index => $tab) {
            $extract = new SheetExtract();
            $extract->setIdentifier($tab);
            $extract->setTabName(
                $sheets
                    ->spreadsheets
                    ->get($id)[$index]
                    ->getProperties()
                    ->getTitle()
            );

            $extract->setNumberOfRows(
                $sheets
                    ->spreadsheets
                    ->get($id)[$index]
                    ->getProperties()
                    ->getGridProperties()
                    ->getRowCount()
            );

            for ($i = 1; $i <= $extract->getNumberOfRows(); $i = $i + 500) {
                $rows = $sheets
                    ->spreadsheets_values
                    ->get(
                        $id,
                        sprintf(
                            '%s!A%d:Z%d',
                            $extract->getTabName(),
                            $i,
                            min($i + 500, $extract->getNumberOfRows())
                        )
                    )
                    ->getValues();

                if ($rows) {
                    $extract->addRows($rows);
                } else {
                    break;
                }
            }

            $extracts->addTab($extract);
        }

        return $extracts;
    }

    private function extractVolunteers(SheetExtract $extract) : array
    {
        $volunteers = [];
        foreach ($extract->getRows() as $index => $row) {
            // Skip the first row, which is the header
            if ($index == 0) {
                continue;
            }

            $id = $row[0];
            if (!$id) {
                continue;
            }

            $volunteer = new VolunteerExtract();
            $volunteer->setId($id);

            // Phones are rows I and J
            $this->populatePhone($volunteer, $row[8], 'A');
            $this->populatePhone($volunteer, $row[9], 'B');

            // Emails are rows K and L
            $this->populateEmail($volunteer, $row[10], 'A');
            $this->populateEmail($volunteer, $row[11], 'B');

            //$this->checkInconsistencies($volunteer);

            $volunteers[] = $volunteer;
        }

        return $volunteers;
    }

    private function populatePhone(VolunteerExtract $extract, ?string $phoneNumber, string $letter) : void
    {
        if (null === $phoneNumber || empty($phoneNumber)) {
            return;
        }

        try {
            $phoneUtil = PhoneNumberUtil::getInstance();
            $parsed    = $phoneUtil->parse($phoneNumber, 'FR');
            $e164      = $phoneUtil->format($parsed, PhoneNumberFormat::E164);
        } catch (NumberParseException $e) {
            echo sprintf('%s: %s', $phoneNumber, $e->getMessage()), PHP_EOL;

            return;
        }

        if ('A' === $letter) {
            $extract->setPhoneA($e164);
        } else {
            $extract->setPhoneB($e164);
        }

        $volunteer = $this->volunteerManager->findOneByPhoneNumber($e164);

        if ('A' === $letter) {
            $extract->setVolunteerPhoneA($volunteer);
        } else {
            $extract->setVolunteerPhoneB($volunteer);
        }

        if ($volunteer) {
            $extract->setVolunteer($volunteer);
        }
    }

    private function populateEmail(VolunteerExtract $extract, ?string $email, string $letter) : void
    {
        if (null === $email || empty($email)) {
            return;
        }

        if ('A' === $letter) {
            $extract->setEmailA($email);
        } else {
            $extract->setEmailB($email);
        }

        $volunteer = $this->volunteerManager->findOneByEmail($email);

        if ('A' === $letter) {
            $extract->setVolunteerEmailA($volunteer);
        } else {
            $extract->setVolunteerEmailB($volunteer);
        }

        if ($volunteer) {
            $extract->setVolunteer($volunteer);
        }
    }

    private function checkInconsistencies(VolunteerExtract $extract)
    {
        $volunteers = array_unique(array_filter([
            $extract->getVolunteerPhoneA(),
            $extract->getVolunteerPhoneB(),
            $extract->getVolunteerEmailA(),
            $extract->getVolunteerEmailB(),
        ]));

        if (count($volunteers) > 1) {
            echo $extract->getId(), PHP_EOL;
            if ($extract->getPhoneA() && $extract->getVolunteerPhoneA()) {
                echo sprintf("- phone A %s owned by %s (%s %s) on gaia\n", $extract->getPhoneA(), $extract->getVolunteerPhoneA()->getExternalId(), $extract->getVolunteerPhoneA()->getFirstName(), $extract->getVolunteerPhoneA()->getLastName());
            }
            if ($extract->getPhoneB() && $extract->getVolunteerPhoneB()) {
                echo sprintf("- phone B %s owned by %s (%s %s) on gaia\n", $extract->getPhoneB(), $extract->getVolunteerPhoneB()->getExternalId(), $extract->getVolunteerPhoneB()->getFirstName(), $extract->getVolunteerPhoneB()->getLastName());
            }
            if ($extract->getEmailA() && $extract->getVolunteerEmailA()) {
                echo sprintf("- email A %s owned by %s (%s %s) on gaia\n", $extract->getEmailA(), $extract->getVolunteerEmailA()->getExternalId(), $extract->getVolunteerEmailA()->getFirstName(), $extract->getVolunteerEmailA()->getLastName());
            }
            if ($extract->getEmailB() && $extract->getVolunteerEmailB()) {
                echo sprintf("- email B %s owned by %s (%s %s) on gaia\n", $extract->getEmailB(), $extract->getVolunteerEmailB()->getExternalId(), $extract->getVolunteerEmailB()->getFirstName(), $extract->getVolunteerEmailB()->getLastName());
            }
            echo "\n";
        }
    }
}