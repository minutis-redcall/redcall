<?php

namespace App;

class Queues
{
    // CSV-based daily sync (replaces the legacy Pegass cache pipeline)
    const SYNC_START    = 'sync-start';
    const SYNC_CHUNK    = 'sync-chunk';
    const SYNC_FINALIZE = 'sync-finalize';

    // Legacy queues — kept only until commit 7 removes the legacy tasks.
    const PEGASS_CREATE_CHUNKS = 'pegass-create-chunks';
    const PEGASS_UPDATE_CHUNK  = 'pegass-update-chunk';
    const SYNC_WITH_PEGASS_ALL = 'sync-with-pegass-all';
    const SYNC_WITH_PEGASS_ONE = 'sync-with-pegass-one';

    const CREATE_TRIGGER = 'create-trigger';
    const MESSAGES_SMS   = 'messages-sms';
    const MESSAGES_CALL  = 'messages-call';
    const MESSAGES_EMAIL = 'messages-email';
}