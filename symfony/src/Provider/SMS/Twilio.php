<?php

namespace App\Provider\SMS;

use Bundles\TwilioBundle\SMS\Twilio as BaseTwilio;

class Twilio extends BaseTwilio implements SMSProvider
{
    /**
     * {@inheritdoc}
     */
    public function send(string $phoneNumber, string $message, array $context = []): SMSSent
    {
        $twilioMessage = parent::sendMessage($phoneNumber, $message, $context);

        // With Twilio, we get cost of the message asynchronously
        return new SMSSent($twilioMessage->getSid(), 0.0, 'USD');
    }
}
