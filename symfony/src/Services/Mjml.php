<?php

namespace App\Services;

use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;

class Mjml
{
    /**
     * @var HttpBrowser
     */
    private $client;

    public function convert(string $mjml) : string
    {
        $this->getClient()->request('POST', 'https://api.mjml.io/v1/render', [], [], [
            'HTTP_AUTHORIZATION' => sprintf('Basic %s', base64_encode(sprintf('%s:%s', getenv('MJML_APPLICATION_ID'), getenv('MJML_PRIVATE_KEY')))),
            'HTTP_CONTENT_TYPE' => 'Application/Json',
        ], json_encode([
            'mjml' => $mjml,
        ]));

        $response = json_decode($this->getClient()->getResponse()->getContent(), true);

        return $response['html'];
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