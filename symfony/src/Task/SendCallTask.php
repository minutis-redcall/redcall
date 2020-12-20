<?php

namespace App\Task;

use App\Queues;

class SendCallTask extends AbstractSendMessageTask
{
    public function getQueueName() : string
    {
        return Queues::MESSAGES_CALL;
    }
}