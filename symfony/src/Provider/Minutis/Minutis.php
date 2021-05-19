<?php

namespace App\Provider\Minutis;

use App\Model\MinutisToken;
use App\Settings;
use Bundles\SettingsBundle\Manager\SettingManager;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class Minutis implements MinutisProvider
{
    /**
     * @var SettingManager
     */
    private $settingManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Client
     */
    private $client;

    public function __construct(SettingManager $settingManager, LoggerInterface $logger)
    {
        $this->settingManager = $settingManager;
        $this->logger         = $logger;
    }

    static public function getOperationUrl(int $operationExternalId) : string
    {
        return sprintf('%soperation/%s/moyens', getenv('MINUTIS_URL'), $operationExternalId);
    }

    public function searchForOperations(string $structureExternalId,
        string $criteria = null) : array
    {
        $response = $this->getClient()->get('/api/regulation', $this->populateAuthentication([
            'query' => [
                'parentExternalId' => sprintf('red_cross_france_leaf_%s', $structureExternalId),
                'nom'              => $criteria,
            ],
        ]));

        $operations = [];
        foreach (json_decode($response->getBody()->getContents(), true) as $operation) {
            $operations[] = [
                'id'   => $operation['id'],
                'name' => sprintf('%s (%s)', $operation['nom'], $operation['owner']),
            ];
        }

        usort($operations, function ($a, $b) {
            return $b['id'] <=> $a['id'];
        });

        return $operations;
    }

    public function isOperationExisting(int $operationExternalId) : bool
    {
        try {
            $this->getClient()->get(sprintf('/api/regulation/%d', $operationExternalId), $this->populateAuthentication([]));

            return true;
        } catch (ClientException $exception) {
            if (Response::HTTP_NOT_FOUND === $exception->getResponse()->getStatusCode()) {
                return false;
            }

            throw $exception;
        }
    }

    public function searchForVolunteer(string $volunteerExternalId) : ?array
    {
        $nivol = str_pad($volunteerExternalId, 12, '0', STR_PAD_LEFT);

        $response = $this->getClient()->get('/api/ressource', $this->populateAuthentication([
            'query' => [
                'type'       => 'benevole',
                'externalId' => $nivol,
            ],
        ]));

        $result = json_decode($response->getBody()->getContents(), true)['entities'];

        if (!$result) {
            $this->logger->error(sprintf('Cannot find volunteer with external id "%s" in Minutis', $nivol));

            return null;
        }

        return reset($result);
    }

    public function createOperation(string $structureExternalId, string $name, string $ownerEmail) : int
    {
        $response = $this->getClient()->post('/api/regulation', $this->populateAuthentication([
            'json' => [
                'parentExternalId' => sprintf('red_cross_france_leaf_%s', $structureExternalId),
                'nom'              => $name,
                'owner'            => $ownerEmail,
            ],
        ]));

        $payload = json_decode($response->getBody()->getContents(), true);

        return $payload['id'];
    }

    public function addResourceToOperation(int $externalOperationId, string $volunteerExternalId) : ?int
    {
        $resource = $this->searchForVolunteer($volunteerExternalId);

        if (!$resource) {
            return null;
        }

        $response = $this->getClient()->post(sprintf('/api/regulation/%d/ressource', $externalOperationId), $this->populateAuthentication([
            'json' => [
                'locked'       => true,
                'regulationId' => $externalOperationId,
                'ressource'    => $resource,
            ],
        ]));

        $payload = json_decode($response->getBody()->getContents(), true);

        return $payload['id'];
    }

    public function removeResourceFromOperation(int $externalOperationId, int $resourceExternalId)
    {
        $this->getClient()->delete(sprintf('/api/regulation/%d/ressource/%d', $externalOperationId, $resourceExternalId), $this->populateAuthentication());
    }

    private function populateAuthentication(array $config = [])
    {
        return array_merge_recursive($config, [
            'headers' => [
                'Authorization' => sprintf('Bearer %s', $this->getToken()),
            ],
        ]);
    }

    private function getToken() : MinutisToken
    {
        $cypher = $this->settingManager->get(Settings::MINUTIS_TOKEN);
        if (!$cypher) {
            return $this->createToken();
        }

        $token = MinutisToken::unserialize($cypher);

        if ($token->isAccessTokenExpired()) {
            return $this->createToken();
        }

        return $token;
    }

    private function createToken() : MinutisToken
    {
        $response = $this->getClient()->post('/api/auth', [
            'json' => [
                'username' => getenv('MINUTIS_SA_USERNAME'),
                'password' => getenv('MINUTIS_SA_PASSWORD'),
            ],
        ]);

        $payload = json_decode($response->getBody()->getContents(), true);

        $token = new MinutisToken();
        $token->setAccessToken($payload['accessToken']);
        $token->setAccessTokenExpiresAt(time() + $payload['accessTokenTimeout']);

        $this->settingManager->set(Settings::MINUTIS_TOKEN, $token->serialize());

        return $token;
    }

    private function getClient() : Client
    {
        if (null === $this->client) {
            $this->client = new Client([
                'base_uri' => getenv('MINUTIS_URL'),
                'timeout'  => 3,
            ]);
        }

        return $this->client;
    }
}
