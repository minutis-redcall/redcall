<?php

namespace App\ParamConverter;

use App\Model\Csrf;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CsrfParamConverter implements ParamConverterInterface
{
    /**
     * @var CsrfTokenManagerInterface
     */
    private $csrfTokenManager;

    /**
     * @param CsrfTokenManagerInterface $csrfTokenManager
     */
    public function __construct(CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->csrfTokenManager = $csrfTokenManager;
    }

    public function apply(Request $request, ParamConverter $configuration)
    {
        $id = $configuration->getName();

        if (!$request->attributes->has($id)) {
            return false;
        }

        $token = $request->attributes->get($id);

        if (!$token && $configuration->isOptional()) {
            return false;
        }

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken($id, $token))) {
            throw new NotFoundHttpException('Invalid CSRF token');
        }

        $request->attributes->set($id, new Csrf());

        return true;
    }

    public function supports(ParamConverter $configuration)
    {
        return Csrf::class === $configuration->getClass();
    }
}