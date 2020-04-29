<?php

namespace App\Communication\Processor;

use App\Entity\Communication;
use Google\Cloud\Tasks\V2\AppEngineHttpRequest;
use Google\Cloud\Tasks\V2\CloudTasksClient;
use Google\Cloud\Tasks\V2\HttpMethod;
use Google\Cloud\Tasks\V2\Task;

class QueueProcessor implements ProcessorInterface
{
    public function process(Communication $communication)
    {
        $queueId = $this->getQueueId($communication);

        $client = new CloudTasksClient();
        $queueName = $client->queueName(getenv('GCP_PROJECT_NAME'), getenv('GCP_PROJECT_LOCATION'), $queueId);

        foreach ($communication->getMessages() as $message) {
            $payload = json_encode(['message_id' => $message->getId()]);

            $httpRequest = new AppEngineHttpRequest();
            $httpRequest->setRelativeUri('/task/message');
            $httpRequest->setHttpMethod(HttpMethod::POST);
            $httpRequest->setBody($payload);

            $task = new Task();
            $task->setAppEngineHttpRequest($httpRequest);

            $client->createTask($queueName, $task);
        }
    }

    private function getQueueId(Communication $communication)
    {
        $queueId = null;
        switch ($communication->getType()) {
            case Communication::TYPE_SMS:
                $queueId = getenv('GCP_QUEUE_SMS');
                break;
            case Communication::TYPE_CALL:
                $queueId = getenv('GCP_QUEUE_CALL');
                break;
            case Communication::TYPE_EMAIL:
                $queueId = getenv('GCP_QUEUE_EMAIL');
                break;
            default:
                throw new \LogicException(
                    sprintf('Invalid communication type given: %s', $communication->getType())
                );
        }

        return $queueId;
    }
}