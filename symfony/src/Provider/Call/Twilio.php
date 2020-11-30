<?php

namespace App\Provider\Call;

use Bundles\TwilioBundle\Manager\TwilioCallManager;

class Twilio extends TwilioCallManager implements CallProvider
{
    public function send(string $from, string $to, array $context = []) : ?string
    {
        $call = parent::sendCall($from, $to, true, $context);

        return $call->getSid();
    }
}