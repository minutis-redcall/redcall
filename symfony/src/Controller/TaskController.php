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

    public function __construct(MessageManager $messageManager, Sender $sender, HttpKernelInterface $httpKernel)
    {
        $this->messageManager = $messageManager;
        $this->sender         = $sender;
        $this->httpKernel     = $httpKernel;
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
        if (!($data['WebhookRequest'] ?? null)) {
            throw $this->createNotFoundException();
        }
        $params = $data['WebhookRequest'];

        switch ($params['origin']) {
            case getenv('GCP_QUEUE_WEBHOOK_RESPONSE'):
                $uri = '/twilio/incoming-message';
                break;
            case getenv('GCP_QUEUE_WEBHOOK_STATUS'):
                $uri = sprintf('/twilio/status%s', $params['uri']);
                break;

            case 'test':
                $uri = '/task/test';
                break;

            default:
                throw $this->createNotFoundException();
        }

        $server = [];
        foreach ($params['headers'] ?? [] as $key => $value) {
            $server[strtoupper(str_replace('-', '_', $key))] = $value;
        }

        $subRequest = Request::create(
            $uri.(($params['queryParams'] ?? false) ? '?'.http_build_query($params['queryParams']) : ''),
            $params['method'] ?? 'GET',
            [],
            [],
            [],
            $server,
            $params['body'] ?? ''
        );

        return $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }

    /**
     * @Route("/test")
     */
    public function test(Request $request, LoggerInterface $slackLogger)
    {
        $slackLogger->info(sprintf("Webhook test controller reached out!\n- headers: %s\n- query: %s\n- body: %s\n",
            json_encode($request->headers->all()),
            json_encode($request->query->all()),
            $request->getContent()
        ));

        return new Response();
    }

    private function checkOrigin(Request $request)
    {
        if (!$request->headers->get('X-Appengine-Taskname')) {
            throw $this->createAccessDeniedException();
        }
    }
}