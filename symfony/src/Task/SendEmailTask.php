<?php

namespace App\Task;

class SendEmailTask extends AbstractSendMessageTask
{
    public function getQueueName() : string
    {
        return getenv('GCP_QUEUE_EMAIL');
    }
}