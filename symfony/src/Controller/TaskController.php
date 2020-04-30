<?php

namespace App\Controller;

use App\Base\BaseController;
use App\Communication\Sender;
use App\Manager\MessageManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="task")
 */
class TaskController extends BaseController
{
    /**
     * @var MessageManager
     */
    private $messageManager;

    /**
     * @var Sender
     */
    private $sender;

    /**
     * @param MessageManager $messageManager
     * @param Sender         $sender
     */
    public function __construct(MessageManager $messageManager, Sender $sender)
    {
        $this->messageManager = $messageManager;
        $this->sender = $sender;
    }

    /**
     * @Route("/message")
     */
    public function message(Request $request)
    {
        // Checking that request comes from App Engine
        if (!$request->headers->get('X-Appengine-Taskname')) {
            throw $this->createAccessDeniedException();
        }

        $payload = json_decode($request->getContent(), true) ?? null;
        if (!$payload) {
            return new Response();
        }

        $message = $this->messageManager->find($payload['message_id'] ?? 0);
        if (!$message) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $this->sender->sendMessage($message, false);

        return new Response();
    }
}