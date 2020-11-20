<?php

namespace Bundles\TwilioBundle\Controller;

use Bundles\TwilioBundle\Component\HttpFoundation\XmlResponse;
use Bundles\TwilioBundle\Manager\TwilioMessageManager;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(name="twilio_", path="/twilio/")
 */
class MessageController extends BaseController
{
    /**
     * @var TwilioMessageManager
     */
    private $messageManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(RequestStack $requestStack,
        TwilioMessageManager $messageManager,
        LoggerInterface $logger = null)
    {
        parent::__construct($requestStack);

        $this->messageManager = $messageManager;
        $this->logger         = $logger ?? new NullLogger();
    }

    /**
     * @Route(name="incoming_message", path="incoming-message")
     */
    public function incoming(Request $request)
    {
        $this->validateRequestSignature($request);

        $this->logger->info('Twilio webhooks - incoming message', [
            'payload' => $request->request->all(),
        ]);

        $response = $this->messageManager->handleInboundMessage(
            array_merge(
                $request->query->all(),
                $request->request->all()
            )
        );

        if (!$response) {
            return new Response();
        }

        return new XmlResponse($response->asXml());
    }
}
