<?php

namespace Bundles\PasswordLoginBundle\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Exception\LogoutException;
use Twig\Environment;

class CsrfExceptionListener
{
    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var string
     */
    private $homeRoute;

    public function __construct(Environment $twig, string $homeRoute)
    {
        $this->twig = $twig;
        $this->homeRoute = $homeRoute;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if ($exception instanceof LogoutException || $exception instanceof InvalidCsrfTokenException) {
            $response = new Response();

            $response->setContent(
                $this->twig->render('@PasswordLogin/error/csrf.html.twig', [
                    'homeRoute' => $this->homeRoute,
                ])
            );

            $event->setResponse($response);
        }

    }
}