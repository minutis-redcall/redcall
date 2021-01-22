<?php

namespace Bundles\ApiBundle\Listener;

use Bundles\ApiBundle\Contracts\ApiExceptionInterface;
use Bundles\ApiBundle\Error\HttpError;
use Bundles\ApiBundle\Error\ThrowableError;
use Bundles\ApiBundle\Model\Facade\ErrorFacade;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\SerializerInterface;

class ExceptionListener
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $request = $event->getRequest();
        if (0 !== strpos($request->getPathInfo(), '/api/')) {
            return;
        }

        $throwable = $event->getThrowable();

        if ($throwable instanceof HttpException) {
            $error = new HttpError($throwable);
        } elseif ($throwable instanceof ApiExceptionInterface) {
            $error = $throwable->getError();
        } else {
            $error = new ThrowableError($throwable);;
        }

        $facade = new ErrorFacade();
        $facade->setCode($error->getCode());
        $facade->setMessage($error->getMessage());
        $facade->setContext($error->getContext());

        $event->setResponse(
            new Response(
                $this->serializer->serialize($facade, 'json'),
                $error->getStatus()
            )
        );
    }
}