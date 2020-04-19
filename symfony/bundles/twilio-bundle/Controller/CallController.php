<?php

namespace Bundles\TwilioBundle\Controller;

use Bundles\TwilioBundle\Component\HttpFoundation\XmlResponse;
use Bundles\TwilioBundle\Manager\TwilioCallManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

        $response = $this->callManager->handleIncomingCall(
            $request->request->all()
        );

        if (!$response) {
            return new Response();
        }

        return new XmlResponse($response->asXml());
    }

    /**
     * @Route(name="outgoing_call", path="outgoing-call/{uuid}")
     */
    public function outgoing(Request $request, string $uuid)
    {
        $this->validateRequestSignature($request);

        $this->logger->info('Twilio webhooks - outgoing call', [
            'payload' => $request->request->all(),
        ]);

        $call = $this->callManager->get($uuid);
        if (!$call) {
            throw $this->createNotFoundException();
        }

        $keys = $request->get('Digits');
        if (null === $keys) {
            $response = $this->callManager->handleCallEstablished($call);
        } else {
            $response = $this->callManager->handleKeyPressed($call, $keys);
        }

        if ($response) {
            return new XmlResponse($response->asXml());
        }

        return $this->redirectToRoute('twilio_outgoing_call', [
            'uuid' => $uuid,
        ]);
    }
}
