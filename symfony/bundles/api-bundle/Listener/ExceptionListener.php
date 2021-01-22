<?php

namespace Bundles\ApiBundle\Listener;

use Bundles\ApiBundle\Contracts\ApiExceptionInterface;
use Bundles\ApiBundle\Error\HttpError;
use Bundles\ApiBundle\Error\ThrowableError;
use Bundles\ApiBundle\Model\Facade\ErrorFacade;
use Psr\Log\LoggerInterface;
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

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(SerializerInterface $serializer, LoggerInterface $logger)
    {
        $this->serializer = $serializer;
        $this->logger     = $logger;
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

        if ($error->getCode() >= Response::HTTP_INTERNAL_SERVER_ERROR) {
            $this->logger->error($throwable->getMessage(), [
                'trace' => $throwable->getTraceAsString(),
            ]);
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