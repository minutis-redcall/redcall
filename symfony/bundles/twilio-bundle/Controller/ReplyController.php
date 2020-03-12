<?php

namespace Bundles\TwilioBundle\Controller;

use Bundles\TwilioBundle\SMS\Twilio;
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
     * @param Twilio $twilio
     */
    public function __construct(Twilio $twilio)
    {
        $this->twilio = $twilio;
    }

    /**
     * @Route(name="reply", path="reply")
     */
    public function reply(Request $request)
    {
        $this->validateRequestSignature($request);

        $sid = $request->get('MessageSid');
        if (!$sid) {
            throw new BadRequestHttpException();
        }

        $this->twilio->handleReply($sid);

        return new Response();
    }
}
