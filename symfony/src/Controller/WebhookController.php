<?php

namespace App\Controller;

use App\Base\BaseController;
use App\Communication\Dispatcher;
use App\Entity\Message;
use Nexmo\Message\InboundMessage;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends BaseController
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

            return new Response();
        }

        $message = $this->getManager(Message::class)->getMessageFromPhoneNumberAndPrefix($inbound->getFrom(), $inbound->getBody());

        if ($message && $message->getCommunication()->getCampaign()->isActive()) {
            $this->dispatcher->processInboundAnswer($message, $inbound->getBody());
        }

        $this->logger->info('SMS Inbound received!', [
            'sender' => $inbound->getFrom(),
            'inbound' => $inbound->getBody(),
        ]);

        return new Response();
    }
}