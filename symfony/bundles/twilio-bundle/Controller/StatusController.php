<?php

namespace Bundles\TwilioBundle\Controller;

use Bundles\TwilioBundle\Entity\TwilioStatus;
use Bundles\TwilioBundle\Event\TwilioEvent;
use Bundles\TwilioBundle\Manager\TwilioMessageManager;
use Bundles\TwilioBundle\Manager\TwilioStatusManager;
use Bundles\TwilioBundle\TwilioEvents;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @Route(name="twilio_", path="/twilio/")
 */
class StatusController extends BaseController
{
    /**
     * @var TwilioMessageManager
     */
    private $messageManager;

    /**
     * @var TwilioStatusManager
     */
    private $statusManager;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @param TwilioMessageManager     $messageManager
     * @param TwilioStatusManager      $statusManager
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(TwilioMessageManager $messageManager, TwilioStatusManager $statusManager, EventDispatcherInterface $eventDispatcher)
    {
        $this->messageManager = $messageManager;
        $this->statusManager = $statusManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @Route(name="status", path="status/{uuid}")
     * @Template()
     */
    public function status(Request $request, string $uuid)
    {
        $this->validateRequestSignature($request);

        $outbound = $this->messageManager->get($uuid);

        if ($outbound) {
            $outbound->setStatus($request->get('MessageStatus'));
            $this->messageManager->save($outbound);
            $this->eventDispatcher->dispatch(new TwilioEvent($outbound), TwilioEvents::STATUS_UPDATED);
            $this->messageManager->save($outbound);
        }

        $status = new TwilioStatus();
        $status->setSid($request->get('MessageSid'));
        $status->setStatus($request->get('MessageStatus'));
        $this->statusManager->save($status);

        return new Response();
    }
}
