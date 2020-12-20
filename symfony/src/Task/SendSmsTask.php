<?php

namespace App\Task;

use App\Queues;

class SendSmsTask extends AbstractSendMessageTask
{
    public function getQueueName() : string
    {
        return Queues::MESSAGES_SMS;
    }
}