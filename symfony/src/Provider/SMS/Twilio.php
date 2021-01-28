<?php

namespace App\Provider\SMS;

use Bundles\TwilioBundle\Manager\TwilioMessageManager as BaseTwilio;

class Twilio extends BaseTwilio implements SMSProvider
{
    public function send(string $from, string $to, string $message, array $context = []) : ?string
    {
        $twilioMessage = parent::sendMessage($to, $message, $context);

        return $twilioMessage->getSid();
    }
}
