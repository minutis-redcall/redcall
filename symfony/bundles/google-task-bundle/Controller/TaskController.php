<?php

namespace Bundles\GoogleTaskBundle\Controller;

use Bundles\GoogleTaskBundle\Service\TaskReceiver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TaskController extends AbstractController
{
    private $taskReceiver;

    public function __construct(TaskReceiver $taskReceiver)
    {
        $this->taskReceiver = $taskReceiver;
    }

    /**
     * @Route(name="google_task_receiver", path="/cloud-task")
     */
    public function receive(Request $request)
    {
        $this->taskReceiver->handle($request);

        return new Response();
    }
}
