<?php

namespace App\Provider\SMS;

use Bundles\TwilioBundle\Manager\TwilioMessageManager as BaseTwilio;
use Ramsey\Uuid\Uuid;

class TwilioWithStatusAsTask extends BaseTwilio implements SMSProvider
{
    /**
     * {@inheritdoc}
     */
    public function send(string $from, string $to, string $message, array $context = []) : ?string
    {
        $uuid = Uuid::uuid4();

        $twilioMessage = parent::sendMessage($from, $to, $message, $context, [
            'messageUuid'    => $uuid,
            'statusCallback' => sprintf(
                'https://%s-%s.cloudfunctions.net/%s/%s',
                getenv('GCP_PROJECT_LOCATION'),
                getenv('GCP_PROJECT_NAME'),
                rtrim(getenv('GCP_FUNCTION_TWILIO_STATUS'), '/'),
                $uuid
            ),
        ]);

        return $twilioMessage->getSid();
    }
}
