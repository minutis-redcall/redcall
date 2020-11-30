<?php

namespace Bundles\TwilioBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class TwilioBundle extends Bundle
{
    public function boot()
    {
        if (!getenv('TWILIO_ACCOUNT_SID') || !getenv('TWILIO_AUTH_TOKEN')) {
            throw new \LogicException('You should set TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN in order to use TwilioBundle.');
        }
    }
}
