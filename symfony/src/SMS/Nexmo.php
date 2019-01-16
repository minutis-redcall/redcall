<?php

namespace App\SMS;

use Nexmo\Client;

class Nexmo implements SMSProvider
{
    /** @var Client */
    private $client;

    /** @var string */
    private $fromNumber;

    public function __construct()
    {
        $this->client = new Client(new Client\Credentials\Basic(
                getenv('NEXMO_API_KEY'), getenv('NEXMO_API_SECRET'))
        );

        $this->fromNumber = getenv('NEXMO_SEND_FROM');
    }

    /**
     * {@inheritdoc}
     */
    public function send(string $message, string $phoneNumber): string
    {
        $message = $this->client->message()->send([
            'to'   => $phoneNumber,
            'from' => $this->fromNumber,
            'text' => $message,
            'type' => 'unicode',
        ]);

        return $message->getMessageId();
    }

    /**
     * {@inheritdoc}
     */
    public function getProviderCode(): string
    {
        return 'nexmo';
    }
}