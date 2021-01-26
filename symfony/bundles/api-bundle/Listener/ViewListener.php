<?php

namespace Bundles\ApiBundle\Listener;

use Bundles\ApiBundle\Contracts\FacadeInterface;
use Bundles\ApiBundle\Fetcher\StatusCodeFetcher;
use Bundles\ApiBundle\Model\Facade\SuccessFacade;
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
     * @var StatusCodeFetcher
     */
    private $statusCodeParser;

    public function __construct(SerializerInterface $serializer, StatusCodeFetcher $statusCodeParser)
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