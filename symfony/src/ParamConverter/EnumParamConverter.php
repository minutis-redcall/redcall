<?php

namespace App\ParamConverter;

use MyCLabs\Enum\Enum;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EnumParamConverter implements ParamConverterInterface
{
    public function apply(Request $request, ParamConverter $configuration)
    {
        $param = $configuration->getName();

        if (!$request->attributes->has($param)) {
            return false;
        }

        $value = $request->attributes->get($param);

        if (!$value && $configuration->isOptional()) {
            return false;
        }

        $enumClass = $configuration->getClass();

        if (!call_user_func([$enumClass, 'isValid'], $value)) {
            throw new NotFoundHttpException();
        }

        $enum = new $enumClass($value);

        $request->attributes->set($param, $enum);

        return true;
    }

    public function supports(ParamConverter $configuration)
    {
        return is_subclass_of($configuration->getClass(), Enum::class);
    }
}