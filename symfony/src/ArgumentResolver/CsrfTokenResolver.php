<?php

namespace App\ArgumentResolver;

use App\Model\Csrf;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CsrfTokenResolver implements ValueResolverInterface
{
    private CsrfTokenManagerInterface $csrfTokenManager;

    public function __construct(CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->csrfTokenManager = $csrfTokenManager;
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (Csrf::class !== $argument->getType()) {
            return [];
        }

        $name = $argument->getName();

        if (!$request->attributes->has($name)) {
            throw new NotFoundHttpException('Invalid CSRF token');
        }

        $token = $request->attributes->get($name);

        if (!$token || !$this->csrfTokenManager->isTokenValid(new CsrfToken($name, $token))) {
            throw new NotFoundHttpException('Invalid CSRF token');
        }

        return [new Csrf()];
    }
}
