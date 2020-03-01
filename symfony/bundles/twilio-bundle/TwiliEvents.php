<?php

namespace Bundles\TwilioBundle;

class TwiliEvents
{
    const MESSAGE_SENT     = 'twilio.message_sent';
    const MESSAGE_RECEIVED = 'twilio.message_received';
    const PRICE_UPDATED    = 'twilio.price_updated';
}
