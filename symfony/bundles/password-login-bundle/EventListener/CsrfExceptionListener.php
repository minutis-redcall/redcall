<?php

namespace Bundles\PasswordLoginBundle\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Exception\LogoutException;
use Twig_Environment;

class CsrfExceptionListener
{
    private $templating;

    public function __construct(Twig_Environment $templating)
    {
        $this->templating = $templating;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if ($exception instanceof LogoutException || $exception instanceof InvalidCsrfTokenException) {
            $response = new Response();

            $response->setContent(
                $this->templating->render('@PasswordLogin/error/csrf.html.twig')
            );

            $event->setResponse($response);
        }

    }
}