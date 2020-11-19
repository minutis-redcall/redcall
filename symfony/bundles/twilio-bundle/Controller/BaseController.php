<?php

namespace Bundles\TwilioBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Twilio\Security\RequestValidator;

abstract class BaseController extends AbstractController
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    protected function validateRequestSignature(Request $request)
    {
        $validator = new RequestValidator(getenv('TWILIO_AUTH_TOKEN'));

        // If we are in a subrequest, it probably comes from an asynchronous task that has been
        // handled in a previous controller. In that case, we expect "absoluteUri" query parameter
        // to contain the absolute url Twilio originally called.
        $isMasterRequest = $this->requestStack->getMasterRequest() === $request;

        $validated = $validator->validate(
            $request->headers->get('X-Twilio-Signature') ?? '',
            $isMasterRequest ? ($request->getUri() ?? '') : $request->get('absoluteUri'),
            $request->request->all()
        );

        if (!$validated) {
            throw new BadRequestHttpException();
        }
    }
}