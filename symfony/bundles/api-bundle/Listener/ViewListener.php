<?php

namespace Bundles\ApiBundle\Listener;

use Bundles\ApiBundle\Contracts\FacadeInterface;
use Bundles\ApiBundle\Model\Facade\SuccessFacade;
use Bundles\ApiBundle\Reader\StatusCodeReader;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\Serializer\SerializerInterface;

class ViewListener
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var StatusCodeReader
     */
    private $statusCodeReader;

    public function __construct(SerializerInterface $serializer, StatusCodeReader $statusCodeReader)
    {
        $this->serializer       = $serializer;
        $this->statusCodeReader = $statusCodeReader;
    }

    public function onKernelView(ViewEvent $event)
    {
        $result = $event->getControllerResult();

        if (!($result instanceof FacadeInterface)) {
            return;
        }

        $facade = new SuccessFacade();
        $facade->setPayload($result);

        $event->setResponse(
            new Response(
                $this->serializer->serialize($facade, 'json'),
                $this->statusCodeReader->getStatusCode($facade)
            )
        );
    }
}