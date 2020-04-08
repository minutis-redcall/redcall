<?php

namespace Bundles\TwilioBundle;

class TwilioEvents
{
    const MESSAGE_SENT          = 'twilio.message_sent';
    const MESSAGE_RECEIVED      = 'twilio.message_received';
    const MESSAGE_PRICE_UPDATED = 'twilio.message_price_updated';

    const CALL_RECEIVED      = 'twilio.call_received';
    const CALL_PRICE_UPDATED = 'twilio.call_price_updated';

    const STATUS_UPDATED = 'twilio.status_updated';
}
