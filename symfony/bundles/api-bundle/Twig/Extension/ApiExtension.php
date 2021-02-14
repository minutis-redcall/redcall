<?php

namespace Bundles\ApiBundle\Twig\Extension;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
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
            new TwigFilter('http_build_query', 'http_build_query'),
        ];
    }

    public function ofType($value, string $type) : bool
    {
        return $type === gettype($value);
    }

    public function jsonPrettify($value)
    {
        return json_encode($value, JSON_PRETTY_PRINT);
    }
}