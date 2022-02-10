<?php

namespace App\Task;

use Bundles\GoogleTaskBundle\Contracts\TaskInterface;
use Bundles\PegassCrawlerBundle\Manager\PegassManager;
use Twig\Environment;

class LoadPegassFilesTask implements TaskInterface
{
    const NAME = 'load-pegass-files';

    /*
     * Missing:
     * redcall_ref_structures: pas besoin des structures inactives
     * redcall_benevoles: le champ âge est un float
     * redcall_benevoles: le champ telephone doit-être au format E164 (cas des numéros de tél DOM/TOM/COM)
     *
     */

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
        $this->extractStructureBasics($csvs);
        $this->extractVolunteerBasics($csvs);

        // check missing structures
        // check closed structures

    }

    public function extractVolunteerBasics(array $csvs)
    {
        // benevoles
        // nivol,nom,prenom,age,email,email_crf,telephone,id_structure
        // 00000342302R,TIEMBLO,ALAIN,38.0,xxx@example.com,alain.tiemblo@croix-rouge.fr,0606060606,889
        foreach ($csvs['benevoles'] as $line) {
            $volunteer = [
                'identifier'         => $line[0],
                'lastname'           => $line[1],
                'firstname'          => $line[2],
                'birthday'           => sprintf('%d-01-01TT00:00:00', date('Y') - intval($line[3])),
                'minor'              => intval(intval($line[3]) < 18),
                'personal_email'     => $line[4],
                'organization_email' => $line[5],
                'phone'              => $line[6],
                'structure_id'       => $line[7],
            ];

            $this->volunteers[$volunteer['identifier']] = $volunteer;
        }
    }

    public function extractStructureBasics(array $csvs)
    {
        // ref_structures
        // structure_id,structure_parent_id,structure_libelle,structure_libelle_court,adresse_numero,adresse_type_voie,adresse_voie,adresse_lieu_dit,adresse_code_postal,adresse_commune
        // 889,80,UNITE LOCALE DE PARIS 1ER ET 2EME,UL PARIS0102,1,Rue,DU BEAUJOLAIS,,75001,PARIS 01
        foreach ($csvs['ref_structures'] as $line) {
            $structure = [
                'id'            => $line[0],
                'parent_id'     => $line[1],
                'label'         => $line[2],
                'short_label'   => $line[3],
                'address'       => preg_replace('/\s+/u', ' ', mb_strtoupper(sprintf('%d %s %s %s %s', $line[4], $line[5], $line[6], $line[8], $line[9]))),
                'responsible'   => null,
                'volunteer_ids' => [],
            ];

            $this->structures[$structure['id']] = $structure;
        }
    }

    public function decsvize(array $context) : array
    {
        // Replace keys without dates and extensions:
        // benevoles
        // competences_acquises
        // formes
        // groupes_actions_menees
        // nommes
        // ref_competences
        // ref_formations
        // ref_groupes_actions
        // ref_nominations
        // ref_structures
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