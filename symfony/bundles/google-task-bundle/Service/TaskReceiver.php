<?php

namespace Bundles\GoogleTaskBundle\Service;

use Bundles\GoogleTaskBundle\Bag\TaskBag;
use Bundles\GoogleTaskBundle\Security\Signer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class TaskReceiver
{
    private $taskBag;

    public function __construct(TaskBag $taskBag)
    {
        $this->taskBag = $taskBag;
    }

    public function handle(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        if (!$payload) {
            throw new BadRequestHttpException('Request does not have a body');
        }

        if (!Signer::verify($payload)) {
            throw new BadRequestHttpException('Request signature is invalid');
        }

        $task = $this->taskBag->getTask($payload['name']);
        $task->execute($payload['context']);
    }
}