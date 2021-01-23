<?php

namespace Bundles\ApiBundle\Listener;

use Bundles\ApiBundle\Contracts\FacadeInterface;
use Bundles\ApiBundle\Model\Facade\SuccessFacade;
use Bundles\ApiBundle\Parser\StatusCodeParser;
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
     * @var StatusCodeParser
     */
    private $statusCodeParser;

    public function __construct(SerializerInterface $serializer, StatusCodeParser $statusCodeParser)
    {
        $this->serializer       = $serializer;
        $this->statusCodeParser = $statusCodeParser;
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
                $this->statusCodeParser->getStatusCode($facade)
            )
        );
    }
}