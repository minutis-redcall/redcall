<?php

namespace App\Twig\Extension;

use App\Tools\Random;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    protected $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('http_build_query', 'http_build_query', ['is_safe' => ['html', 'html_attr']]),
            new TwigFunction('random', [$this, 'random']),
            new TwigFunction('uuid', [$this, 'uuid']),
            new TwigFunction('intval', 'intval'),
        ];
    }

    public function getFilters()
    {
        return [
            new TwigFilter('snake', [$this, 'snake']),
            new TwigFilter('js_json', [$this, 'jsJson'], ['is_safe' => ['html', 'js']]),
        ];
    }

    /**
     * JSON safe to embed in a <script> block: <, >, &, ' and " are
     * hex-escaped so user data can never close the block or open a comment.
     */
    public function jsJson($value) : string
    {
        return json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
    }

    public function uuid() : string
    {
        return Uuid::uuid4();
    }

    public function random(int $size = 16) : string
    {
        return Random::generate($size);
    }

    public function snake(string $camelCase)
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $camelCase));
    }

    public function getName()
    {
        return 'app';
    }
}
