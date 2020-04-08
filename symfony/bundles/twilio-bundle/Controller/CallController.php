<?php

namespace Bundles\TwilioBundle\Controller;

use Blablacar\Bundle\MainBundle\Test\Helper\Response;
use Bundles\TwilioBundle\Component\HttpFoundation\XmlResponse;
use Bundles\TwilioBundle\Manager\TwilioCallManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(name="twilio_", path="/twilio/")
 */
class CallController extends BaseController
{
    /**
     * @var TwilioCallManager
     */
    private $callManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param TwilioCallManager    $callManager
     * @param LoggerInterface|null $logger
     */
    public function __construct(TwilioCallManager $callManager, LoggerInterface $logger = null)
    {
        $this->callManager = $callManager;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @Route(name="incoming_call", path="incoming-call")
     */
    public function incoming(Request $request)
    {
        $this->validateRequestSignature($request);

        $this->logger->info('Twilio webhooks - incoming call', [
            'payload' => $request->request->all(),
        ]);

        $response = $this->callManager->handleIncomingCall($request->request->all());
        if (!$response) {
            return new Response();
        }

        return new XmlResponse($response->asXml());
    }
}
