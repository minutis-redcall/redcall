<?php

namespace Bundles\PegassCrawlerBundle\Service;

use Bundles\PegassCrawlerBundle\Entity\Pegass;
use Goutte\Client;
use Symfony\Component\HttpClient\HttpClient;

class PegassClient
{
    const ENDPOINTS = [
        Pegass::TYPE_AREA       => 'https://pegass.croix-rouge.fr/crf/rest/zonegeo',
        Pegass::TYPE_DEPARTMENT => 'https://pegass.croix-rouge.fr/crf/rest/zonegeo/departement/%identifier%',
        Pegass::TYPE_STRUCTURE  => [
            'structure'   => 'https://pegass.croix-rouge.fr/crf/rest/structure/%identifier%',
            'responsible' => 'https://pegass.croix-rouge.fr/crf/rest/structure/responsable/?structure=%identifier%',
            'volunteers'  => 'https://pegass.croix-rouge.fr/crf/rest/utilisateur?page=%page%&pageInfo=true&perPage=50&searchType=benevoles&structure=%identifier%&withMoyensCom=true',
        ],
        Pegass::TYPE_VOLUNTEER  => [
            'user'        => 'https://pegass.croix-rouge.fr/crf/rest/utilisateur/%identifier%',
            'infos'       => 'https://pegass.croix-rouge.fr/crf/rest/infoutilisateur/%identifier%',
            'contact'     => 'https://pegass.croix-rouge.fr/crf/rest/moyencomutilisateur?utilisateur=%identifier%',
            'actions'     => 'https://pegass.croix-rouge.fr/crf/rest/structureaction?utilisateur=%identifier%',
            'skills'      => 'https://pegass.croix-rouge.fr/crf/rest/competenceutilisateur/%identifier%',
            'trainings'   => 'https://pegass.croix-rouge.fr/crf/rest/formationutilisateur?utilisateur=%identifier%',
            'nominations' => 'https://pegass.croix-rouge.fr/crf/rest/nominationutilisateur?utilisateur=%identifier%',
        ],
    ];

    const MODE_FAST = 'fast';
    const MODE_SLOW = 'slow';

    private $client;
    private $authenticated = false;
    private $mode          = self::MODE_FAST;

    public function getMode(): string
    {
        return $this->mode;
    }

    public function setMode(string $mode): PegassClient
    {
        $this->mode = $mode;

        return $this;
    }

    public function getArea(): array
    {
        return $this->get(self::ENDPOINTS[Pegass::TYPE_AREA]);
    }

    public function getDepartment(string $identifier): array
    {
        $endpoint = str_replace('%identifier%', $identifier, self::ENDPOINTS[Pegass::TYPE_DEPARTMENT]);

        return $this->get($endpoint);
    }

    public function getStructure(string $identifier): array
    {
        $structure = [];
        foreach (self::ENDPOINTS[Pegass::TYPE_STRUCTURE] as $key => $endpoint) {
            if ('volunteers' !== $key) {
                $endpoint        = str_replace('%identifier%', $identifier, $endpoint);
                $structure[$key] = $this->get($endpoint);
            }
        }

        $pages = [];

        do {
            $endpoint = str_replace([
                '%identifier%',
                '%page%',
            ], [
                $identifier,
                ($data['page'] ?? -1) + 1,
            ], self::ENDPOINTS[Pegass::TYPE_STRUCTURE]['volunteers']);

            $pages[] = $data = $this->get($endpoint);
        } while (count($data['list']) && $data['page'] < $data['pages']);

        $structure['volunteers'] = $pages;

        return $structure;
    }

    public function getVolunteer(string $identifier): array
    {
        $data = [];
        foreach (self::ENDPOINTS[Pegass::TYPE_VOLUNTEER] as $key => $endpoint) {
            $endpoint   = str_replace('%identifier%', $identifier, $endpoint);
            $data[$key] = $this->get($endpoint);
        }

        return $data;
    }

    private function isAuthenticated(): bool
    {
        return $this->authenticated;
    }

    private function authenticate()
    {
        if ($this->isAuthenticated()) {
            return;
        }

        if (!getenv('PEGASS_LOGIN') || !getenv('PEGASS_PASSWORD')) {
            throw new \LogicException('Credentials are required to access Pegass API.');
        }

        $crawler = $this->getClient()->request('GET', 'https://pegass.croix-rouge.fr/');
        $form    = $crawler->selectButton('Ouverture de session')->form();

        $crawler = $this->getClient()->submit($form, [
            'username' => getenv('PEGASS_LOGIN'),
            'password' => getenv('PEGASS_PASSWORD'),
        ]);
        $form    = $crawler->selectButton('Continue')->form();

        $this->getClient()->submit($form);

        $this->authenticated = true;
    }

    private function get(string $url): array
    {
        $this->authenticate();

        // DDOS prone, should only be used by cron jobs
        if (self::MODE_SLOW === $this->getMode()) {
            usleep(500000);
        }

        $this->getClient()->request('GET', $url);

        return json_decode($this->getClient()->getResponse()->getContent(), true);
    }

    private function getClient(): Client
    {
        if (!$this->client) {
            $this->client = new Client(HttpClient::create([
                'max_redirects' => 10,
            ]));
        }

        return $this->client;
    }
}