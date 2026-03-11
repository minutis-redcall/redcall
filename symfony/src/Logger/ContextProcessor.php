<?php

namespace App\Logger;

use Monolog\LogRecord;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ContextProcessor
{
    private $kernel;
    private $tokenStorage;
    private $requestStack;

    public function __construct(KernelInterface $kernel,
        TokenStorageInterface $tokenStorage,
        RequestStack $requestStack)
    {
        $this->kernel       = $kernel;
        $this->tokenStorage = $tokenStorage;
        $this->requestStack = $requestStack;
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $extra = $record->extra;
        $extra['env']      = $this->kernel->getEnvironment();
        $extra['platform'] = php_sapi_name();

        if ($request = $this->requestStack->getMainRequest()) {
            $extra['uri'] = $request->getUri();
            if ($request->getContent()) {
                $extra['body'] = $request->getContent();
            }

            if ($this->tokenStorage->getToken() && $this->tokenStorage->getToken()->getUser() instanceof UserInterface) {
                $extra['user'] = $this->tokenStorage->getToken()->getUser()->getUserIdentifier();
            }
        }

        return $record->with(extra: $extra);
    }
}