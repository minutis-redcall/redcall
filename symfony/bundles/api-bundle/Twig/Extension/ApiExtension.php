<?php

namespace Bundles\ApiBundle\Twig\Extension;

use Bundles\ApiBundle\Entity\Token;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ApiExtension extends AbstractExtension
{
    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(Environment $twig, RouterInterface $router, RequestStack $requestStack)
    {
        $this->twig         = $twig;
        $this->router       = $router;
        $this->requestStack = $requestStack;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('api_demo', [$this, 'apiDemo'], ['is_safe' => ['html']]),
        ];
    }

    public function apiDemo(Token $token, string $method, string $uri, array $body, bool $prettyPrint = true)
    {
        return $this->twig->render('@Api/demo.html.twig', [
            'method' => $method,
            'base'   => $this->getMasterRequest()->getSchemeAndHttpHost(),
            'uri'    => $uri,
            'body'   => json_encode($body, $prettyPrint ? JSON_PRETTY_PRINT : 0),
            'token'  => $token,
        ]);
    }

    private function getMasterRequest() : Request
    {
        return $this->requestStack->getMasterRequest();
    }
}