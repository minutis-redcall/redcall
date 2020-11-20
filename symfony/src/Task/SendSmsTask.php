<?php

namespace App\Task;

class SendSmsTask extends AbstractSendMessageTask
{
    public function getQueueName() : string
    {
        return getenv('GCP_QUEUE_SMS');
    }
}