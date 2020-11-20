<?php

namespace App\Task;

class SendCallTask extends AbstractSendMessageTask
{
    public function getQueueName() : string
    {
        return getenv('GCP_QUEUE_CALL');
    }
}