<?php

namespace Bundles\ApiBundle\Twig\Extension;

use Bundles\ApiBundle\Entity\Token;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class ApiExtension extends AbstractExtension
{
    /**
     * @var Environment
     */
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('api_demo', [$this, 'apiDemo'], ['is_safe' => ['html']]),
        ];
    }

    public function getTests()
    {
        return [
            new TwigTest('of type', [$this, 'ofType']),
        ];
    }

    public function getFilters()
    {
        return [
            new TwigFilter('json_prettify', [$this, 'jsonPrettify']),
        ];
    }

    public function apiDemo(Token $token, string $method, string $uri, array $body, bool $prettyPrint = true)
    {
        return $this->twig->render('@Api/demo.html.twig', [
            'method' => $method,
            'uri'    => $uri,
            'body'   => json_encode($body, $prettyPrint ? JSON_PRETTY_PRINT : 0),
            'token'  => $token,
        ]);
    }

    public function ofType($value, string $type) : bool
    {
        return $type === gettype($value);
    }

    public function jsonPrettify($value, $isAlreadyJson = true)
    {
        if ($isAlreadyJson) {
            $value = json_decode($value, true);
        }

        return json_encode($value, JSON_PRETTY_PRINT);
    }
}