<?php

namespace App;

class Queues
{
    // Update Pegass entities
    const PEGASS_CREATE_CHUNKS = 'pegass-create-chunks';
    const PEGASS_UPDATE_CHUNK  = 'pegass-update-chunk';

    // Refresh RedCall entities against Pegass table
    const SYNC_WITH_PEGASS_ALL = 'sync-with-pegass-all';
    const SYNC_WITH_PEGASS_ONE = 'sync-with-pegass-one';

    const CREATE_TRIGGER = 'create-trigger';
    const MESSAGES_SMS   = 'messages-sms';
    const MESSAGES_CALL  = 'messages-call';
    const MESSAGES_EMAIL = 'messages-email';
}