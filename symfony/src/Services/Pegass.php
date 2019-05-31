<?php

namespace App\Services;

use App\Entity\Organization;
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

    public function getVolunteerSkills(string $volunteerId) : array
    {

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