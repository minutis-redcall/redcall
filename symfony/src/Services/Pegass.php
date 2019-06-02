<?php

namespace App\Services;

use App\Entity\Organization;
use App\Entity\Tag;
use App\Entity\Volunteer;

class Pegass
{
    protected $client;
    protected $authenticated = false;

    public function __construct()
    {
        $this->client = new \Goutte\Client([
            'cookies' => true,
            'allow_redirects' => true,
        ]);
    }

    public function isAuthenticated() : bool
    {
        return $this->authenticated;
    }

    public function authenticate()
    {
        if ($this->isAuthenticated()) {
            return ;
        }

        $crawler = $this->client->request('GET', 'https://pegass.croix-rouge.fr/');
        $form = $crawler->selectButton('Ouverture de session')->form();

        $crawler = $this->client->submit($form, [
            'username' => getenv('PEGASS_LOGIN'),
            'password' => getenv('PEGASS_PASSWORD'),
        ]);
        $form = $crawler->selectButton('Continue')->form();

        $this->client->submit($form);

        $this->authenticated = true;
    }

    public function listDepartments() : array
    {
        $rows = $this->get('https://pegass.croix-rouge.fr/crf/rest/zonegeo');

        $departments = [];
        foreach ($rows as $row) {
            $departments[] = [
                'code' => $row['id'],
                'name' => mb_strtoupper($row['nom']),
            ];
        }

        return $departments;
    }

    public function listOrganizations(string $departmentId) : array
    {
        $rows = $this->get(sprintf('https://pegass.croix-rouge.fr/crf/rest/zonegeo/departement/%s', $departmentId));

        $organizations = [];
        foreach ($rows['structuresFilles'] as $row) {
            $organization = new Organization();
            $organization->setCode($row['id']);
            $organization->setType($row['typeStructure']);
            $organization->setName($row['libelle']);

            $organizations[] = $organization;
        }

        return $organizations;
    }

    public function listVolunteers(string $organizationCode) : array
    {
        $volunteers = [];
        $template = 'https://pegass.croix-rouge.fr/crf/rest/utilisateur?page=%page%&pageInfo=true&perPage=50&searchType=benevoles&structure=%code%&withMoyensCom=true';
        do {
            $data = $this->get(str_replace(['%code%', '%page%'], [$organizationCode, ($data['page'] ?? -1) + 1], $template));

            foreach ($data['list'] as $row) {
                $volunteer = new Volunteer();
                $volunteer->setFirstName($row['prenom']);
                $volunteer->setLastName($row['nom']);
                $volunteer->setNivol($row['id']);
                $volunteer->setEnabled($row['actif']);
                $volunteer->setMinor($row['mineur']);

                foreach ($row['coordonnees'] ?? [] as $contact) {
                    if (in_array($contact['moyenComId'], ['MAIL', 'MAILDOM'])
                        && preg_match('/^.+\@.+\..+$/', $contact['libelle'] ?? false)
                        && stripos($contact['libelle'] ?? false, 'croix-rouge.fr') === false) {
                        $volunteer->setEmail($contact['libelle']);
                    }

                    if (in_array($contact['moyenComId'], ['POR', 'PORE', 'PORT', 'TELDOM', 'TELTRAV']) && $contact['libelle'] ?? false) {
                        $phone = $this->parsePhone($contact['libelle']);
                        if ($phone) {
                            $volunteer->setPhoneNumber($phone);
                        }
                    }
                }

                foreach ($row['coordonnees'] ?? [] as $contact) {
                    if (in_array($contact['moyenComId'], ['MAIL', 'MAILDOM'])
                        && preg_match('/^.+\@.+\..+$/', $contact['libelle'] ?? false)
                        && !$volunteer->getEmail()) {
                        $volunteer->setEmail($contact['libelle']);
                    }
                }

                $volunteers[] = $volunteer;
            }

        } while (count($data['list']) && $data['page'] < $data['pages']);

        return $volunteers;
    }

    public function getVolunteerTags(string $volunteerId) : array
    {
        // US / AS ?
        $skills = $this->getVolunteerActions($volunteerId);

        // VL / VPSP ?
        $skills = array_merge($skills, $this->getVolunteerVehicles($volunteerId));

        // PSC1, PSE1/rec, PSE2/rec, CI
        $skills = array_merge($skills, $this->getVolunteerSkills($volunteerId));

        return $skills;
    }

    public function getVolunteerActions(string $volunteerId) : array
    {
        $actions = $this->get(sprintf('https://pegass.croix-rouge.fr/crf/rest/structureaction?utilisateur=%s', $volunteerId));
        $skills = [];

        foreach ($actions as $action) {
            if (1 == $action['groupeAction']['id']) {
                $skills[] = Tag::TAG_EMERGENCY_ASSISTANCE;
            }

            if (2 == $action['groupeAction']['id']) {
                $skills[] = Tag::TAG_SOCIAL_ASSISTANCE;
            }
        }

        return array_unique($skills);
    }

    public function getVolunteerVehicles(string $volunteerId) : array
    {
        $roles = $this->get(sprintf('https://pegass.croix-rouge.fr/crf/rest/competenceutilisateur/%s', $volunteerId));
        $skills = [];

        foreach ($roles as $role) {
            if (9 == $role['id']) {
                $skills[] = Tag::TAG_DRVR_VL;
            }

            if (10 == $role['id']) {
                $skills[] = Tag::TAG_DRVR_VPSP;
            }
        }

        return array_unique($skills);
    }

    public function getVolunteerSkills(string $volunteerId) : array
    {
        $skills = $this->get(sprintf('https://pegass.croix-rouge.fr/crf/rest/formationutilisateur?utilisateur=%s', $volunteerId));
        $tags = [];

        foreach ($skills as $skill) {
            if ('RECCI' == $skill['formation']['code']) {
                $tags[] = Tag::TAG_CI_R;
            }

            if (in_array($skill['formation']['code'], ['CI', 'CIP3'])) {
                $tags[] = Tag::TAG_CI_I;
            }

            if ('RECPSE2' == $skill['formation']['code']) {
                $tags[] = Tag::TAG_PSE_2_R;
            }

            if ('PSE2' == $skill['formation']['code']) {
                $tags[] = Tag::TAG_PSE_2_I;
            }

            if ('RECPSE1' == $skill['formation']['code']) {
                $tags[] = Tag::TAG_PSE_1_R;
            }

            if ('PSE1' == $skill['formation']['code']) {
                $tags[] = Tag::TAG_PSE_1_I;
            }

            if (in_array($skill['formation']['code'], ['RECPSC1', 'PSC1'])) {
                $tags[] = Tag::TAG_PSC_1;
            }
        }

        return array_unique($tags);
    }

    /**
     * @param string $url
     *
     * @return array
     */
    private function get(string $url) : array
    {
        $this->authenticate();

        $this->client->request('GET', $url);

        return json_decode($this->client->getResponse()->getContent(), true);
    }

    /**
     * @param string $phone
     *
     * @return null|string
     */
    private function parsePhone(string $phone) : ?string
    {
        $phone = ltrim(preg_replace('/[^0-9]/', '', $phone), 0);
        if (strlen($phone) == 9) {
            $phone = '33'.ltrim($phone, 0);
        }

        if (strlen($phone) != 11) {
            return null;
        }

        if (!in_array(substr($phone, 0, 3), ['336', '337'])) {
            return null;
        }

        return $phone;
    }
}