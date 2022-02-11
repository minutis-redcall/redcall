<?php

namespace App\Task;

use Bundles\GoogleTaskBundle\Contracts\TaskInterface;
use Bundles\PegassCrawlerBundle\Manager\PegassManager;
use Twig\Environment;

class LoadPegassFilesTask implements TaskInterface
{
    const NAME = 'load-pegass-files';

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
     * @var Environment
     */

    private $twig;

    public function __construct(PegassManager $pegassManager, Environment $twig)
    {
        $this->pegassManager = $pegassManager;
        $this->twig          = $twig;
    }

    public function execute(array $context)
    {
        $csvs = $this->decsvize($context);

        // Structures
        $this->extractStructureBasics($csvs);

        // Volunteers
        $this->extractVolunteerBasics($csvs);
        $this->extractActions($csvs);
        $this->extractSkills($csvs);
        $this->extractTrainings($csvs);
        $this->extractNominations($csvs);

        // check missing structures
        // check closed structures

    }

    public function extractNominations(array $csvs)
    {
        // ref_nominations
        // id,libelle
        // 108,Chef de Secteur
        $nominations = [];
        foreach ($csvs['ref_nominations'] as $row) {
            $nominations[$row[0]] = [
                'code'  => '',
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

            if ($row[1] && !in_array($volunteerIdentifier, $this->structures[$row[1]]['volunteer_ids'])) {
                $this->structures[$row[1]]['volunteer_ids'][] = $volunteerIdentifier;
            }

            $this->volunteers[$volunteerIdentifier]['nominations'][] = [
                'id'           => $row[2],
                'label'        => $nominations[$row[2]],
                'structure_id' => $row[1],
                'got_at'       => $row[3] ? \DateTime::createFromFormat('d/m/Y', $row[3]) : null,
            ];
        }
    }

    public function extractTrainings(array $csvs)
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

            $this->volunteers[$volunteerIdentifier]['trainings'][] = [
                'id'     => $row[1],
                'code'   => $trainings[$row[1]]['code'],
                'label'  => $trainings[$row[1]]['label'],
                'got_at' => $row[2] ? \DateTime::createFromFormat('d/m/Y', $row[2]) : null,
                'rec_at' => $row[3] ? \DateTime::createFromFormat('d/m/Y', $row[3]) : null,
            ];
        }
    }

    public function extractSkills(array $csvs)
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

            $this->volunteers[$volunteerIdentifier]['skills'][] = [
                'id'    => $row[1],
                'label' => $skills[$row[1]],
            ];
        }
    }

    public function extractActions(array $csvs)
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

    public function extractVolunteerBasics(array $csvs)
    {
        // benevoles
        // nivol,nom,prenom,age,email,email_crf,telephone,id_structure
        // 00000342302R,TIEMBLO,ALAIN,38.0,xxx@example.com,alain.tiemblo@croix-rouge.fr,0606060606,889
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

    public function extractStructureBasics(array $csvs)
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

    public function decsvize(array $context) : array
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

            $csvs[substr($filename, 8, -13)] = $csv;
        }

        return $csvs;
    }

    public function getQueueName() : string
    {
        return self::NAME;
    }
}