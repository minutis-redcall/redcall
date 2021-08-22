<?php

namespace Bundles\ApiBundle\Listener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

class RequestListener
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (0 === strpos($event->getRequest()->getPathInfo(), '/api')) {
            $this->translator->setLocale('en');
        }
    }
}