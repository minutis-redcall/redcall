<?php

namespace Bundles\TwilioBundle\Controller;

use Bundles\TwilioBundle\Manager\TwilioMessageManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
     * @param TwilioMessageManager $messageManager
     */
    public function __construct(TwilioMessageManager $messageManager)
    {
        $this->messageManager = $messageManager;
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
        }

        return new Response();
    }
}
