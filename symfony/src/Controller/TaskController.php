<?php

namespace App\Controller;

use App\Base\BaseController;
use App\Communication\Sender;
use App\Manager\MessageManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

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
     * @var HttpKernelInterface
     */
    private $httpKernel;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(MessageManager $messageManager,
        Sender $sender,
        HttpKernelInterface $httpKernel,
        RouterInterface $router)
    {
        $this->messageManager = $messageManager;
        $this->sender         = $sender;
        $this->httpKernel     = $httpKernel;
        $this->router         = $router;
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
        $this->checkOrigin($request);

        $logger->info('Task webhook endpoint was hit', [
            'payload' => $request->getContent(),
        ]);

        $data = json_decode($request->getContent(), true);
        if (!($data['WebhookRequest'] ?? null)) {
            throw $this->createNotFoundException();
        }
        $params = $data['WebhookRequest'];

        switch ($params['origin']) {
            case getenv('GCP_QUEUE_WEBHOOK_RESPONSE'):
                $uri = $this->router->generate('twilio_incoming_message', array_merge($params['queryParams'], [
                    'absoluteUri' => $params['absoluteUri'],
                ]), RouterInterface::ABSOLUTE_URL);
                break;
            case getenv('GCP_QUEUE_WEBHOOK_STATUS'):
                $uri = $this->router->generate('twilio_status', array_merge($params['queryParams'], [
                    'uuid'        => ltrim($params['relativeUri'], '/'),
                    'absoluteUri' => $params['absoluteUri'],
                ]), RouterInterface::ABSOLUTE_URL);
                break;
            default:
                throw $this->createNotFoundException();
        }

        $subRequest = Request::create($uri);
        $subRequest->query->add($params['queryParams']);
        $subRequest->request->add($params['body']);
        $subRequest->headers->add($params['headers']);

        return $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }

    private function checkOrigin(Request $request)
    {
        if (!$name = $request->headers->get('X-Appengine-QueueName')) {
            throw $this->createAccessDeniedException();
        }
    }
}