<?php

namespace App\Provider\SMS;

use Bundles\TwilioBundle\Manager\TwilioMessageManager as BaseTwilio;
use Ramsey\Uuid\Uuid;

class TwilioWithStatusAsTask extends BaseTwilio implements SMSProvider
{
    public function send(string $from, string $to, string $message, array $context = []) : ?string
    {
        $uuid = Uuid::uuid4();

        $twilioMessage = parent::sendMessage($from, $to, $message, $context, [
            'messageUuid' => $uuid,
        ]);

        return $twilioMessage->getSid();
    }
}
