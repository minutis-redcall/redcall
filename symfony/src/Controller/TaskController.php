<?php

namespace App\Controller;

use App\Base\BaseController;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * @Route(path="task")
 */
class TaskController extends BaseController
{
    /**
     * @var HttpKernelInterface
     */
    private $httpKernel;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(HttpKernelInterface $httpKernel, RouterInterface $router, RequestStack $requestStack)
    {
        $this->httpKernel   = $httpKernel;
        $this->router       = $router;
        $this->requestStack = $requestStack;
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

        $subRequest->setSession(
            $this->requestStack->getMainRequest()->getSession()
        );

        return $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }

    private function checkOrigin(Request $request)
    {
        if (!$name = $request->headers->get('X-Appengine-QueueName')) {
            throw $this->createAccessDeniedException();
        }
    }
}