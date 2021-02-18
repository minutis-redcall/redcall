<?php

namespace App\Logger;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ContextProcessor
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(KernelInterface $kernel,
        TokenStorageInterface $tokenStorage,
        RequestStack $requestStack)
    {
        $this->kernel       = $kernel;
        $this->tokenStorage = $tokenStorage;
        $this->requestStack = $requestStack;
    }

    public function __invoke(array $record)
    {
        $record['extra']['env']      = $this->kernel->getEnvironment();
        $record['extra']['platform'] = php_sapi_name();
        if ($request = $this->requestStack->getMasterRequest()) {
            $record['extra']['uri'] = $request->getUri();
            if ($request->getContent()) {
                $record['extra']['body'] = $request->getContent();
            }
        }

        if ($this->tokenStorage->getToken() && $this->tokenStorage->getToken()->getUser() instanceof UserInterface) {
            $record['extra']['user'] = $this->tokenStorage->getToken()->getUser()->getUsername();
        }

        return $record;
    }
}