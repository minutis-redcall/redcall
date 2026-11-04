<?php

namespace App\Logger;

use Monolog\LogRecord;
use Symfony\Component\HttpFoundation\Request;
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
            // Logs end up in Cloud Logging and Slack: never ship secrets there.
            // No body (passwords, tokens, SMS...), no query string, and route
            // parameters (capability URLs like /space/{sessionId}) redacted.
            $extra['method'] = $request->getMethod();
            $extra['uri']    = $this->redactedPath($request);
            if ($route = $request->attributes->get('_route')) {
                $extra['route'] = $route;
            }
            if ($keys = array_keys($request->request->all())) {
                $extra['body_keys'] = $keys;
            }

            if ($this->tokenStorage->getToken() && $this->tokenStorage->getToken()->getUser() instanceof UserInterface) {
                $extra['user'] = $this->tokenStorage->getToken()->getUser()->getUserIdentifier();
            }
        }

        return $record->with(extra: $extra);
    }

    private function redactedPath(Request $request) : string
    {
        $path = $request->getPathInfo();

        foreach ((array) $request->attributes->get('_route_params', []) as $name => $value) {
            if (is_scalar($value) && '' !== (string) $value) {
                $path = str_replace('/'.rawurlencode((string) $value), '/{'.$name.'}', $path);
                $path = str_replace('/'.$value, '/{'.$name.'}', $path);
            }
        }

        return $path;
    }
}
