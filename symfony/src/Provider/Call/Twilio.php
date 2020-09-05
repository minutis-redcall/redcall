<?php

namespace App\Provider\Call;

use Bundles\TwilioBundle\Manager\TwilioCallManager;

class Twilio extends TwilioCallManager implements CallProvider
{
    public function send(string $phoneNumber, array $context = []): ?string
    {
        $call = parent::sendCall($phoneNumber, true, $context);

        return $call->getSid();
    }
}