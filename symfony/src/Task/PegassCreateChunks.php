<?php

namespace App\Task;

use App\Entity\Pegass;
use App\Manager\PegassManager;
use App\Queues;
use Bundles\GoogleTaskBundle\Contracts\TaskInterface;
use Bundles\GoogleTaskBundle\Service\TaskSender;
use Google\Cloud\Storage\StorageClient;
use Google\Cloud\Storage\StorageObject;
use Psr\Log\LoggerInterface;

class PegassCreateChunks implements TaskInterface
{
    /**
     * @var array
     */
    private $structures = [];

    /**
     * @var array
     */
    private $volunteers = [];

    /**
     * @var PegassManager
     */
    private $pegassManager;

    /**
     * @var TaskSender;
     */
    private $async;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(PegassManager $pegassManager, TaskSender $async, LoggerInterface $logger)
    {
        $this->pegassManager = $pegassManager;
        $this->async         = $async;
        $this->logger        = $logger;
    }

    public function execute(array $context)
    {
        //$this->local();
        $this->remote();
    }

    public function getQueueName() : string
    {
        return Queues::PEGASS_CREATE_CHUNKS;
    }

    private function remote()
    {
        $this->logger->warning('Fetching files on GCS...');
        $files = [];
        $items = (new StorageClient())->bucket(getenv('GCP_STORAGE_PEGASS'))->objects();
        foreach ($items as $item) {
            /** @var StorageObject $item */
            if (preg_match('|^redcall_[a-z_]+_[0-9]{8}\.csv$|u', $item->name())) {
                $files[$item->name()] = $item;
            }
        }
        $this->logger->warning('Fetched '.count($files).' files.');

        $byDates = [];
        foreach ($files as $filename => $item) {
            $byDates[substr($filename, -12, -4)][$filename] = $item;
        }
        krsort($byDates);
        $lastFiles = array_shift($byDates);

        if (10 !== count($lastFiles)) {
            // Export is incomplete
            return;
        }

        $this->logger->warning('Deleting older files...');
        foreach ($byDates as $files) {
            foreach ($files as $filename => $item) {
                $item->delete();
            }
        }

        $this->logger->warning('Downloading files...');
        $context = [];
        foreach ($lastFiles as $filename => $item) {
            $context[$filename] = $item->downloadAsString();
        }

        $this->processFiles($context);
    }

    private function local()
    {
        $this->processFiles(array_combine(
            glob('/Users/alain/Desktop/pegass/redcall_*.csv'),
            array_map(function (string $filename) {
                return file_get_contents($filename);
            }, glob('/Users/alain/Desktop/pegass/redcall_*.csv'))
        ));
    }

    private function processFiles(array $context)
    {
        $csvs = $this->decsvize($context);

        // Structures
        $this->logger->warning('Extracting structure basics...');
        $this->extractStructureBasics($csvs);

        // Volunteers
        $this->logger->warning('Extracting volunteer basics...');
        $this->extractVolunteerBasics($csvs);

        $this->logger->warning('Extracting volunteer actions...');
        $this->extractActions($csvs);

        $this->logger->warning('Extracting volunteer skills...');
        $this->extractSkills($csvs);

        $this->logger->warning('Extracting volunteer trainings...');
        $this->extractTrainings($csvs);

        $this->logger->warning('Extracting volunteer nominations...');
        $this->extractNominations($csvs);

        $this->logger->warning('Updating structures...');
        $this->updateStructures();

        $this->logger->warning('Updating volunteers...');
        $this->updateVolunteers();

        $this->logger->warning('Cleaning missing entities...');
        $this->cleanMissingEntities();

        $this->logger->warning('Job finished.');
    }

    private function updateVolunteers()
    {
        $chunks = array_chunk($this->volunteers, 100, true);

        foreach ($chunks as $chunk) {
            $this->async->fire(PegassUpdateChunk::class, [
                'type'  => Pegass::TYPE_VOLUNTEER,
                'chunk' => $chunk,
            ]);
        }
    }

    private function updateStructures()
    {
        $chunks = array_chunk($this->structures, 10, true);

        foreach ($chunks as $chunk) {
            $this->async->fire(PegassUpdateChunk::class, [
                'type'  => Pegass::TYPE_STRUCTURE,
                'chunk' => $chunk,
            ]);
        }
    }

    private function cleanMissingEntities()
    {
        $keys = array_keys($this->structures);
        unset($this->structures);
        $this->pegassManager->removeMissingEntities(Pegass::TYPE_STRUCTURE, $keys);

        $keys = array_keys($this->volunteers);
        unset($this->volunteers);
        $this->pegassManager->removeMissingEntities(Pegass::TYPE_VOLUNTEER, $keys);

        $this->async->fire(PegassUpdateOneEntity::class, [
            'type' => SyncOneWithPegass::PARENT_STRUCUTRES,
        ]);

        $this->async->fire(PegassUpdateOneEntity::class, [
            'type' => SyncOneWithPegass::SYNC_STRUCTURES,
        ]);

        $this->async->fire(PegassUpdateOneEntity::class, [
            'type' => SyncOneWithPegass::SYNC_VOLUNTEERS,
        ]);
    }

    private function extractNominations(array $csvs)
    {
        // ref_nominations
        // id,libelle,libelle_court
        // 108,Chef de Secteur,CS
        $nominations = [];
        foreach ($csvs['ref_nominations'] as $row) {
            $nominations[$row[0]] = [
                'code'  => $row[2],
                'label' => $row[1],
            ];
        }

        // nommes
        // nivol,id_structure,id_nomination,date_validation,date_fin
        // 00001086784K,99,362,04/02/2022,03/02/2023
        foreach ($csvs['nommes'] as $row) {
            if (!array_key_exists($volunteerIdentifier = $row[0], $this->volunteers)) {
                continue;
            }

            if (!array_key_exists($row[1], $this->structures)) {
                continue;
            }

            if (!array_key_exists($row[2], $nominations)) {
                continue;
            }

            if ($row[1] && !in_array($volunteerIdentifier, $this->structures[$row[1]]['volunteer_ids'])) {
                $this->structures[$row[1]]['volunteer_ids'][] = $volunteerIdentifier;
            }

            $this->volunteers[$volunteerIdentifier]['nominations'][] = [
                'id'           => $row[2],
                'code'         => $nominations[$row[2]]['code'],
                'label'        => $nominations[$row[2]]['label'],
                'structure_id' => $row[1],
                'got_at'       => $row[3] ? \DateTime::createFromFormat('d/m/Y', $row[3])->format('Y-m-d\TH:i:s') : null,
            ];
        }
    }

    private function extractTrainings(array $csvs)
    {
        // ref_formations
        // id,code,libelle
        // 17,CI,CHEF D'INTERVENTION
        $trainings = [];
        foreach ($csvs['ref_formations'] as $row) {
            $trainings[$row[0]] = [
                'code'  => $row[1],
                'label' => $row[2],
            ];
        }

        // formes
        // nivol,id_formation,date_obtention,date_recyclage
        // 00001086784K,166,28/01/2012,31/12/2021
        foreach ($csvs['formes'] as $row) {
            if (!array_key_exists($volunteerIdentifier = $row[0], $this->volunteers)) {
                continue;
            }

            if (!array_key_exists($row[1], $trainings)) {
                continue;
            }

            $this->volunteers[$volunteerIdentifier]['trainings'][] = [
                'id'     => $row[1],
                'code'   => $trainings[$row[1]]['code'],
                'label'  => $trainings[$row[1]]['label'],
                'got_at' => $row[2] ? \DateTime::createFromFormat('d/m/Y', $row[2])->format('Y-m-d\TH:i:s') : null,
                'rec_at' => $row[3] ? \DateTime::createFromFormat('d/m/Y', $row[3])->format('Y-m-d\TH:i:s') : null,
            ];
        }
    }

    private function extractSkills(array $csvs)
    {
        // ref_competences
        // id,libelle
        // 10,Chauffeur VPSP
        $skills = [];
        foreach ($csvs['ref_competences'] as $row) {
            $skills[$row[0]] = $row[1];
        }

        // competences_acquises
        // nivol,id_competence
        // 00001086784K,9
        foreach ($csvs['competences_acquises'] as $row) {
            if (!array_key_exists($volunteerIdentifier = $row[0], $this->volunteers)) {
                continue;
            }

            if (!array_key_exists($row[1], $skills)) {
                continue;
            }

            $this->volunteers[$volunteerIdentifier]['skills'][] = [
                'id'    => $row[1],
                'label' => $skills[$row[1]],
            ];
        }
    }

    private function extractActions(array $csvs)
    {
        // ref_groupes_actions
        // id,libelle
        // 1,Urgence et Secourisme
        $groupActions = [];
        foreach ($csvs['ref_groupes_actions'] as $row) {
            $groupActions[$row[0]] = $row[1];
        }

        // groupes_actions_menees
        // nivol,id_structure,id_groupe_action
        // 00000342302R,889,1
        foreach ($csvs['groupes_actions_menees'] as $row) {
            if (!array_key_exists($volunteerIdentifier = $row[0], $this->volunteers)) {
                continue;
            }

            if (!array_key_exists($structureIdentifier = $row[1], $this->structures)) {
                continue;
            }

            if (!array_key_exists($row[2], $groupActions)) {
                continue;
            }

            if (!in_array($volunteerIdentifier, $this->structures[$structureIdentifier]['volunteer_ids'])) {
                $this->structures[$structureIdentifier]['volunteer_ids'][] = $volunteerIdentifier;
            }

            $this->volunteers[$volunteerIdentifier]['actions'][] = [
                'structure_id'       => $structureIdentifier,
                'group_action_id'    => $row[2],
                'group_action_label' => $groupActions[$row[2]],
            ];
        }
    }

    private function extractVolunteerBasics(array $csvs)
    {
        // benevoles
        // nivol,nom,prenom,age,email,email_crf,telephone,id_structure
        // 00000342302R,TIEMBLO,ALAIN,38.0,xxx@example.com,alain.tiemblo@croix-rouge.fr,+33606060606,889
        foreach ($csvs['benevoles'] as $row) {
            $this->volunteers[$row[0]] = [
                'identifier'         => $row[0],
                'lastname'           => $row[1],
                'firstname'          => $row[2],
                'birthday'           => sprintf('%d-01-01TT00:00:00', date('Y') - intval($row[3])),
                'minor'              => intval(intval($row[3]) < 18),
                'personal_email'     => $row[4],
                'organization_email' => $row[5],
                'phone'              => $row[6],
                'structure_id'       => $row[7],
                'actions'            => [],
                'skills'             => [],
                'trainings'          => [],
                'nominations'        => [],
            ];
        }
    }

    private function extractStructureBasics(array $csvs)
    {
        // ref_structures
        // structure_id,structure_parent_id,structure_libelle,structure_libelle_court,adresse_numero,adresse_type_voie,adresse_voie,adresse_lieu_dit,adresse_code_postal,adresse_commune
        // 889,80,UNITE LOCALE DE PARIS 1ER ET 2EME,UL PARIS0102,1,Rue,DU BEAUJOLAIS,,75001,PARIS 01
        foreach ($csvs['ref_structures'] as $row) {
            $this->structures[$row[0]] = [
                'id'            => $row[0],
                'parent_id'     => $row[1],
                'label'         => $row[2],
                'short_label'   => $row[3],
                'address'       => preg_replace('/\s+/u', ' ', mb_strtoupper(sprintf('%d %s %s %s %s', $row[4], $row[5], $row[6], $row[8], $row[9]))),
                'responsible'   => null,
                'volunteer_ids' => [],
            ];
        }
    }

    private function decsvize(array $context) : array
    {
        $csvs = [];
        foreach ($context as $filename => $content) {
            // Create a virtual file
            $fp = fopen("php://temp", 'r+');
            fputs($fp, $content);
            rewind($fp);

            // Reads the CSV handling newlines properly
            $csv = [];
            while (false !== ($data = fgetcsv($fp))) {
                $csv[] = $data;
            }

            fclose($fp);

            // Remove header
            array_shift($csv);

            $csvs[substr(basename($filename), 8, -13)] = $csv;
        }

        return $csvs;
    }
}