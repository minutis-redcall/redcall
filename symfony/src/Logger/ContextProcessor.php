<?php

namespace App\Logger;

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
     * @param KernelInterface       $kernel
     * @param TokenStorageInterface $tokenStorageInterface
     */
    public function __construct(KernelInterface $kernel, TokenStorageInterface $tokenStorageInterface)
    {
        $this->kernel = $kernel;
        $this->tokenStorage = $tokenStorageInterface;
    }

    public function __invoke(array $record)
    {
        $record['extra']['env'] = $this->kernel->getEnvironment();
        $record['extra']['platform'] = php_sapi_name();

        if ($this->tokenStorage->getToken() && $this->tokenStorage->getToken()->getUser() instanceof UserInterface) {
            $record['extra']['user'] = $this->tokenStorage->getToken()->getUser()->getUsername();
        }

        return $record;
    }
}