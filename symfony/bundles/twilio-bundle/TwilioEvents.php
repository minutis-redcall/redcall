<?php

namespace Bundles\TwilioBundle;

class TwilioEvents
{
    const MESSAGE_SENT     = 'twilio.message_sent';
    const MESSAGE_RECEIVED = 'twilio.message_received';
    const PRICE_UPDATED    = 'twilio.price_updated';
    const STATUS_UPDATED   = 'twilio.status_updated';
}
