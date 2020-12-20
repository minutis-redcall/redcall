<?php

namespace App\Task;

use App\Queues;

class SendEmailTask extends AbstractSendMessageTask
{
    public function getQueueName() : string
    {
        return Queues::MESSAGES_EMAIL;
    }
}