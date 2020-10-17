<?php

namespace App\Controller;

use App\Base\BaseController;
use App\Communication\Sender;
use App\Manager\MessageManager;
use Psr\Log\LoggerInterface;
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
        $this->sender         = $sender;
    }

    /**
     * @Route("/message")
     */
    public function message(Request $request)
    {
        $this->checkOrigin($request);

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

    /**
     * @Route("/webhook")
     */
    public function webhook(Request $request, LoggerInterface $logger)
    {
        //$this->checkOrigin($request);

        $logger->info('Task webhook endpoint was hit', [
            'payload' => $request->getContent(),
        ]);

        $data = json_decode($request->getContent(), true);

        // create($uri,
        // $method = 'GET',
        // $parameters = array(),
        // $cookies = array(),
        // $files = array(),
        // $server = array(),
        // $content = null)
        $forward = Request::create();

        return new Response('Hello, world!');
    }

    private function checkOrigin(Request $request)
    {
        // Checking that request comes from App Engine
        if (!$request->headers->get('X-Appengine-Taskname')) {
            throw $this->createAccessDeniedException();
        }
    }
}