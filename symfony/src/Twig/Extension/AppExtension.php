<?php

namespace App\Twig\Extension;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;

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
            new \Twig_SimpleFunction('http_build_query', 'http_build_query', ['is_safe' => ['html', 'html_attr']]),
            new \Twig_SimpleFunction('array_to_query_fields', [$this, 'arrayToQueryFields'], ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('current_route', [$this, 'currentRoute']),
            new \Twig_SimpleFunction('master_request', [$this, 'masterRequest']),
        ];
    }

    /**
     * Used to keep arguments of the query string when generating a new form with method GET.
     * See: App::macros.html.twig
     *
     * @param string $key
     * @param mixed  $value
     * @param string $keyPrefix
     *
     * @return string
     */
    public function arrayToQueryFields($key, $value, $keyPrefix = null)
    {
        $currentKey = $keyPrefix ? $keyPrefix.'['.$key.']' : $key;

        if (is_string($value)) {
            return '<input type="hidden" name="'.htmlentities($currentKey).'" value="'.htmlentities($value).'"/>';
        }

        $inputs = '';
        foreach ($value as $childKey => $childValue) {
            $inputs .= $this->arrayToQueryFields($childKey, $childValue, $currentKey);
        }

        return $inputs;
    }

    public function currentRoute()
    {
        $request = $this->requestStack->getMasterRequest();

        return [
            'name'   => $request->get('_route'),
            'params' => array_merge($request->get('_route_params', []), $request->query->all()),
        ];
    }

    public function masterRequest()
    {
        return $this->requestStack->getMasterRequest();
    }

    public function getName()
    {
        return 'app';
    }
}
