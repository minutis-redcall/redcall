<?php

namespace Bundles\TwilioBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(name="twilio_", path="/twilio/")
 */
class CallController extends BaseController
{
    /**
     * @Route(name="call", path="call")
     * @Template()
     */
    public function default()
    {
        return [];
    }
}
