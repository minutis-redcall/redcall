<?php

namespace App;

class Queues
{
    // CSV-based daily sync
    const SYNC_START    = 'sync-start';
    const SYNC_CHUNK    = 'sync-chunk';
    const SYNC_FINALIZE = 'sync-finalize';

    const CREATE_TRIGGER = 'create-trigger';
    const MESSAGES_SMS   = 'messages-sms';
    const MESSAGES_CALL  = 'messages-call';
    const MESSAGES_EMAIL = 'messages-email';
}