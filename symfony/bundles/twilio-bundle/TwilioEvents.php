<?php

namespace Bundles\TwilioBundle;

class TwilioEvents
{
    const MESSAGE_SENT          = 'twilio.message_sent';
    const MESSAGE_RECEIVED      = 'twilio.message_received';
    const MESSAGE_PRICE_UPDATED = 'twilio.message_price_updated';
    const MESSAGE_ERROR         = 'twilio.message_error';

    const CALL_INITIALIZED   = 'twilio.call_initialized';
    const CALL_ESTABLISHED   = 'twilio.call_established';
    const CALL_KEY_PRESSED   = 'twilio.call_key_pressed';
    const CALL_RECEIVED      = 'twilio.call_received';
    const CALL_PRICE_UPDATED = 'twilio.call_price_updated';
    const CALL_ERROR         = 'twilio.call_error';

    const STATUS_UPDATED = 'twilio.status_updated';
}
