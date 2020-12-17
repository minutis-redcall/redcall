<?php

namespace App\Services;

use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;

class Phrase
{
    /**
     * @var HttpBrowser
     */
    private $client;

    public function getLocales() : array
    {
        $result = $this->query('GET', '/locales');
        $result = json_decode($result, true);

        $locales = [];
        foreach ($result as $locale) {
            $locales[$locale['id']] = $locale['name'];
        }

        return $locales;
    }

    public function download(string $localeId, string $tag) : string
    {
        return $this->query('GET', '/locales/:localeId/download', [
            ':localeId' => $localeId,
        ], [
            'tags'        => $tag,
            'file_format' => 'yml_symfony2',
        ]);
    }

    public function createTranslation(string $tag, string $localeId, string $key, string $value)
    {
        $keyId = $this->searchKey($key);

        if (!$keyId) {
            $keyId = $this->createKey($tag, $key);
        }

        $this->query('POST', '/translations', null, null, [
            'locale_id' => $localeId,
            'key_id'    => $keyId,
            'content'   => $value,
        ]);
    }

    public function searchKey(string $key) : ?string
    {
        $response = json_decode($this->query('POST', '/keys/search', null, null, [
            'q' => $key,
        ]), true);

        return $response ? reset($response)['id'] : null;
    }

    public function createKey(string $tag, string $key) : string
    {
        $response = json_decode($this->query('POST', '/keys', null, null, [
            'name' => $key,
            'tags' => $tag,
        ]), true);

        return $response['id'];
    }

    public function removeKey(string $key)
    {
        $keyId = $this->searchKey($key);

        if (!$keyId) {
            return;
        }

        $this->query('DELETE', '/keys/:keyId', [
            ':keyId' => $keyId,
        ]);
    }

    private function query(string $method,
        string $path,
        ?array $placeholders = null,
        ?array $queryParams = null,
        ?array $body = null) : string
    {
        $this->getClient()->request($method,
            sprintf(
                'https://api.phrase.com/v2/projects/%s%s%s',
                getenv('PHRASEAPP_PROJECT_ID'),
                str_replace(array_keys($placeholders ?? []), array_values($placeholders ?? []), $path),
                $queryParams ? sprintf('?%s', http_build_query($queryParams)) : ''
            )
            , [], [], [
                'HTTP_AUTHORIZATION' => sprintf('token %s', getenv('PHRASEAPP_API_TOKEN')),
                'HTTP_USER_AGENT'    => getenv('PHRASEAPP_USER_AGENT'),
                'HTTP_CONTENT_TYPE'  => 'Application/Json',
            ], $body ? json_encode($body, JSON_PRETTY_PRINT) : null);

        //echo "{$method} {$path}: ", $placeholders ? sprintf('(%s):', json_encode($placeholders)) : '', $this->getClient()->getResponse()->getStatusCode(), PHP_EOL;

        $response = $this->getClient()->getResponse()->getContent();

        return $response;
    }

    private function getClient() : HttpBrowser
    {
        if (!$this->client) {
            $this->client = new HttpBrowser(
                HttpClient::create()
            );
        }

        return $this->client;
    }
}