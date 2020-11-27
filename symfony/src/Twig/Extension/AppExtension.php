<?php

namespace App\Twig\Extension;

use App\Tools\Random;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig_SimpleFunction;

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
            new Twig_SimpleFunction('http_build_query', 'http_build_query', ['is_safe' => ['html', 'html_attr']]),
            new Twig_SimpleFunction('random', [$this, 'random']),
            new Twig_SimpleFunction('uuid', [$this, 'uuid']),
            new Twig_SimpleFunction('intval', 'intval'),
        ];
    }

    public function uuid() : string
    {
        return Uuid::uuid4();
    }

    public function random(int $size = 16) : string
    {
        return Random::generate($size);
    }

    public function getName()
    {
        return 'app';
    }
}
