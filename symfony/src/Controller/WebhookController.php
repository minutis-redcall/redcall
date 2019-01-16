<?php

namespace App\Controller;

use App\Communication\Dispatcher;
use Nexmo\Message\InboundMessage;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
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
    public function webhookDeliveryReceiptAction(Request $request)
    {
        $request = $request->query->all();

        // Check that this is a delivery receipt.
        if (!isset($request['messageId']) OR !isset($request['status'])) {
            $this->logger->error('This is not a delivery receipt');

            return new Response('');
        }

        $em      = $this->getDoctrine()->getManager();
        $repo    = $em->getRepository('App:Message');
        $message = $repo->findOneBy(['messageId' => $request['messageId']]);
        if ($message == null) {
            $this->logger->error("Your message to {$request['msisdn']} (message id {$request['messageId']}) is unknown!");

            return new Response('');
        }

        //Check if the message has been delivered correctly.
        if ($request['status'] == 'delivered') {
            $this->logger->info("Your message to {$request['msisdn']} (message id {$request['messageId']}) was delivered.");
            $this->dispatcher->acknowledgeMessage($message);

        } elseif ($request['status'] == 'accepted') {
            $this->logger->info("Your message to {$request['msisdn']} (message id {$request['messageId']}) was accepted by the carrier.");
        } else {
            $this->logger->error("Your message to {$request['msisdn']} has a status of: {$request['status']}.");
            $this->logger->error("Check err-code {$request['err-code']} against the documentation.");
        }

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