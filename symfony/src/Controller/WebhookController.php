<?php

namespace App\Controller;

use App\Base\BaseController;
use App\Manager\MessageManager;
use Nexmo\Message\InboundMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WebhookController extends BaseController
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MessageManager
     */
    private $messageManager;

    /**
     * @param LoggerInterface $logger
     * @param MessageManager  $messageManager
     */
    public function __construct(LoggerInterface $logger, MessageManager $messageManager)
    {
        $this->logger = $logger;
        $this->messageManager = $messageManager;
    }

    /**
     * @Route(path="webhooks/delivery-receipt", methods={"GET"})
     *
     * @return Response
     */
    public function webhookDeliveryReceiptAction()
    {
        return new Response();
    }

    /**
     * @Route(path="webhooks/inbound-sms", methods={"GET"})
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

        $this->messageManager->handleAnswer($inbound->getFrom(), $inbound->getBody());

        $this->logger->info('SMS Inbound received!', [
            'sender' => $inbound->getFrom(),
            'inbound' => $inbound->getBody(),
        ]);

        return new Response();
    }
}
