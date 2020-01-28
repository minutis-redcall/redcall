<?php

namespace Bundles\PaginationBundle\Twig\Extension;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PaginationExtension extends AbstractExtension
{
    protected $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('master_request', [$this, 'masterRequest']),
        ];
    }

    public function masterRequest(): Request
    {
        return $this->requestStack->getMasterRequest();
    }
}