<?php

namespace App\Services;

use App\Entity\Tag;
use App\Repository\TagRepository;
use App\Repository\VolunteerImportRepository;
use App\Repository\VolunteerRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class VolunteerImporter
{
    // Tag keys and position in the google spreadsheet
    const TAGS = [
        Tag::TAG_EMERGENCY_ASSISTANCE => 8,
        Tag::TAG_SOCIAL_ASSISTANCE    => 9,
        Tag::TAG_PSC_1                => 12,
        Tag::TAG_PSE_1_I              => 13,
        Tag::TAG_PSE_1_R              => 14,
        Tag::TAG_PSE_2_I              => 15,
        Tag::TAG_PSE_2_R              => 16,
        Tag::TAG_DRVR_VL              => 17,
        Tag::TAG_DRVR_VPSP            => 18,
        Tag::TAG_CI_I                 => 19,
        Tag::TAG_CI_R                 => 20,
    ];

    /**
     * @var VolunteerRepository
     */
    private $volunteerRepository;

    /**
     * @var VolunteerImportRepository
     */
    private $volunteerImportRepository;

    /**
     * @var TagRepository
     */
    private $tagRepoistory;

    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;

    /**
     * VolunteerImportCommand constructor.
     *
     * @param VolunteerRepository       $volunteerRepository
     * @param VolunteerImportRepository $volunteerImportRepository
     * @param TagRepository             $tagRepository
     * @param ParameterBagInterface     $parameterBag
     */
    public function __construct(VolunteerRepository $volunteerRepository,
        VolunteerImportRepository $volunteerImportRepository,
        TagRepository $tagRepository,
        ParameterBagInterface $parameterBag)
    {
        $this->volunteerRepository       = $volunteerRepository;
        $this->volunteerImportRepository = $volunteerImportRepository;
        $this->tagRepoistory             = $tagRepository;
        $this->parameterBag              = $parameterBag;
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\ORMException
     */
    public function run()
    {
        // Mandatory so Google will find its certificate using the
        // GOOGLE_APPLICATION_CREDENTIALS environment variable
        chdir(sprintf('%s/../', $this->parameterBag->get('kernel.root_dir')));

        $client = new \Google_Client();
        $client->setScopes([
            \Google_Service_Sheets::SPREADSHEETS_READONLY,
        ]);
        $client->useApplicationDefaultCredentials();

        $sheets = new \Google_Service_Sheets($client);
        $nbRows = $sheets
                      ->spreadsheets
                      ->get(getenv('GOOGLE_SHEETS_VOLUNTEERS_ID'))
                      ->getSheets()[0]
            ->getProperties()
            ->getGridProperties()
            ->getRowCount();

        $this->volunteerImportRepository->begin();

        $tags     = $this->tagRepoistory->findAll();
        $imported = 0;
        for ($i = 1; $i <= $nbRows; $i = $i + 500) {
            $data = $sheets
                ->spreadsheets_values
                ->get(
                    getenv('GOOGLE_SHEETS_VOLUNTEERS_ID'),
                    sprintf('A%d:V%d', $i, min($i + 500, $nbRows)));

            foreach ($data->getValues() ?? [] as $index => $row) {
                if ($i == 1 && $index < 4 || count($row) < 22) {
                    continue;
                }

                $importArray = [
                    'id'          => $i,
                    'nivol'       => trim($row[0]),
                    'lastname'    => trim($row[1]),
                    'firstname'   => trim($row[2]),
                    'minor'       => trim($row[3]),
                    'phone'       => trim($row[4]),
                    'postal_code' => trim($row[5]),
                    'email'       => trim($row[6]),
                    'callable'    => trim($row[21]),
                ];

                $importArray['tags'] = [];
                foreach (self::TAGS as $tagName => $cellNo) {
                    $importArray['tags'][$tagName] = trim($row[$cellNo]);
                }

                $import = $this->volunteerImportRepository->sanitize($importArray);

                $this->volunteerRepository->import($tags, $import);

                if ($row[0]) {
                    $imported++;
                }
            }
        }

        if ($imported > 0) {
            $this->volunteerRepository->disableNonImportedVolunteers();
        } else {
            throw new \RuntimeException('Volunteer spreadsheet is empty or not accessible.');
        }
    }
}
