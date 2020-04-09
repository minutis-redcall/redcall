<?php

namespace Bundles\TwilioBundle\Service;

use Twilio\Rest\Client;

class Twilio
{
    /**
     * @var Client|null
     */
    private $client;

    /**
     * @return Client
     *
     * @throws \Twilio\Exceptions\ConfigurationException
     */
    public function getClient()
    {
        if ($this->client) {
            return $this->client;
        }

        $this->client = new Client(getenv('TWILIO_ACCOUNT_SID'), getenv('TWILIO_AUTH_TOKEN'));

        return $this->client;
    }
}