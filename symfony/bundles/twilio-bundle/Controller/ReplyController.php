<?php

namespace Bundles\TwilioBundle\Controller;

use Bundles\TwilioBundle\Service\Twilio;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(name="twilio_", path="/twilio/")
 */
class ReplyController extends BaseController
{
    /**
     * @var Twilio
     */
    private $twilio;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Twilio          $twilio
     * @param LoggerInterface|null $logger
     */
    public function __construct(Twilio $twilio, LoggerInterface $logger = null)
    {
        $this->twilio = $twilio;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @Route(name="inbound", path="incoming-message")
     */
    public function inbound(Request $request)
    {
        $this->validateRequestSignature($request);

        $this->logger->info('Twilio webhooks - incoming message', [
            'headers' => $request->headers->all(),
            'query' => $request->query->all(),
            'request' => $request->request->all(),
        ]);

        $sid = $request->get('MessageSid');
        if (!$sid) {
            throw new BadRequestHttpException();
        }

        $this->twilio->handleInboundMessage($sid);

        return new Response();
    }
}
