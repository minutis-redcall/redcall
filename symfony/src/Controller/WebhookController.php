<?php

namespace App\Controller;

use App\Communication\Dispatcher;
use Nexmo\Message\InboundMessage;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends Controller
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * WebhookController constructor.
     *
     * @param LoggerInterface $logger
     * @param Dispatcher      $dispatcher
     */
    public function __construct(LoggerInterface $logger, Dispatcher $dispatcher)
    {
        $this->logger     = $logger;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @Route(path="webhooks/delivery-receipt")
     * @Method({"GET"})
     *
     * @return Response
     */
    public function webhookDeliveryReceiptAction()
    {
        return new Response();
    }

    /**
     * @Route(path="webhooks/inbound-sms")
     * @Method({"GET"})
     *
     * @return Response
     */
    public function webhookInboundSMS()
    {
        $inbound = InboundMessage::createFromGlobals();

        if (!$inbound->isValid()) {
            $this->logger->error('invalid message');

            return new Response('');
        }

        $message = $this->get('doctrine')->getRepository('App:Message')->getLastMessageSentToPhone($inbound->getFrom());

        if ($message && $message->getCommunication()->getCampaign()->isActive()) {
            $this->dispatcher->processInboundAnswer($message, $inbound->getBody());
        }

        $this->logger->info('SMS Inbound received!', [
            'inbound' => $inbound->getBody(),
        ]);

        return new Response();
    }
}