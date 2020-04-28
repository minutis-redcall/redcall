<?php

namespace Bundles\TwilioBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Twilio\Security\RequestValidator;

abstract class BaseController extends AbstractController
{
    protected function validateRequestSignature(Request $request)
    {
        $validator = new RequestValidator(getenv('TWILIO_AUTH_TOKEN'));

        $validated = $validator->validate(
            $request->headers->get('X-Twilio-Signature') ?? '',
            $request->getUri() ?? '',
            $request->request->all()
        );

        if (!$validated) {
            throw new BadRequestHttpException();
        }
    }
}