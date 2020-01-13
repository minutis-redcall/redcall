<?php

namespace App\Services;

use App\Entity\Pegass;
use Goutte\Client;

class PegassClient
{
    const ENDPOINTS = [
        Pegass::TYPE_AREA       => 'https://pegass.croix-rouge.fr/crf/rest/zonegeo',
        Pegass::TYPE_DEPARTMENT => 'https://pegass.croix-rouge.fr/crf/rest/zonegeo/departement/%identifier%',
        Pegass::TYPE_STRUCTURE  => 'https://pegass.croix-rouge.fr/crf/rest/utilisateur?page=%page%&pageInfo=true&perPage=50&searchType=benevoles&structure=%identifier%&withMoyensCom=true',
        Pegass::TYPE_VOLUNTEER  => [
            'actions'   => 'https://pegass.croix-rouge.fr/crf/rest/structureaction?utilisateur=%identifier%',
            'skills'    => 'https://pegass.croix-rouge.fr/crf/rest/competenceutilisateur/%identifier%',
            'trainings' => 'https://pegass.croix-rouge.fr/crf/rest/formationutilisateur?utilisateur=%identifier%',
            // TODO get all useful information
        ],
    ];

    const MODE_FAST = 'fast';
    const MODE_SLOW = 'slow';

    private $client;
    private $authenticated = false;
    private $mode          = self::MODE_FAST;

    public function __construct()
    {
        $this->client = new Client([
            'cookies'         => true,
            'allow_redirects' => true,
        ]);
    }

    /**
     * @return string
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * @param string $mode
     *
     * @return PegassClient
     */
    public function setMode(string $mode): PegassClient
    {
        $this->mode = $mode;

        return $this;
    }

    /**
     * @return string
     */
    public function getArea(): array
    {
        return $this->get(self::ENDPOINTS[Pegass::TYPE_AREA]);
    }

    /**
     * @param string $identifier
     */
    public function getDepartment(string $identifier)
    {
        $endpoint = str_replace('%identifier%', $identifier, self::ENDPOINTS[Pegass::TYPE_DEPARTMENT]);

        return $this->get($endpoint);
    }

    /**
     * @param string $identifier
     *
     * @return array
     */
    public function getStructure(string $identifier)
    {
        $pages = [];

        do {
            $endpoint = str_replace([
                '%identifier%',
                '%page%',
            ], [
                $identifier,
                ($data['page'] ?? -1) + 1,
            ], self::ENDPOINTS[Pegass::TYPE_STRUCTURE]);

            $pages[] = $data = $this->get($endpoint);
        } while (count($data['list']) && $data['page'] < $data['pages']);

        return $pages;
    }

    /**
     * @param string $identifier
     */
    public function getVolunteer(string $identifier)
    {
        $endpoint = str_replace('%identifier%', $identifier, self::ENDPOINTS[Pegass::TYPE_VOLUNTEER]);

        return $this->get($endpoint);
    }

    /**
     * @return bool
     */
    private function isAuthenticated(): bool
    {
        return $this->authenticated;
    }

    private function authenticate()
    {
        if ($this->isAuthenticated()) {
            return;
        }

        $crawler = $this->client->request('GET', 'https://pegass.croix-rouge.fr/');
        $form    = $crawler->selectButton('Ouverture de session')->form();

        $crawler = $this->client->submit($form, [
            'username' => getenv('PEGASS_LOGIN'),
            'password' => getenv('PEGASS_PASSWORD'),
        ]);
        $form    = $crawler->selectButton('Continue')->form();

        $this->client->submit($form);

        $this->authenticated = true;
    }

    /**
     * @param string $url
     *
     * @return array
     */
    private function get(string $url): array
    {
        $this->authenticate();

        // DDOS prone, should only be used by cron jobs
        if (self::MODE_SLOW === $this->getMode()) {
            usleep(500000);
        }

        $this->client->request('GET', $url);

        return json_decode($this->client->getResponse()->getContent(), true);
    }
}