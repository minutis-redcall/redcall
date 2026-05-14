<?php

namespace App\ArgumentResolver;

use MyCLabs\Enum\Enum;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MyClabsEnumResolver implements ValueResolverInterface
{
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $type = $argument->getType();

        if (!$type || !is_subclass_of($type, Enum::class)) {
            return [];
        }

        $name = $argument->getName();

        if (!$request->attributes->has($name)) {
            return [];
        }

        $value = $request->attributes->get($name);

        if ($value instanceof $type) {
            // Already resolved — happens with render(controller(..., {type: enumObject}))
            // through the inline fragment renderer, which preserves object references
            // across the sub-request boundary.
            return [$value];
        }

        if (null === $value || '' === $value) {
            if ($argument->isNullable() || $argument->hasDefaultValue()) {
                return [];
            }
            throw new NotFoundHttpException();
        }

        if (!call_user_func([$type, 'isValid'], $value)) {
            throw new NotFoundHttpException();
        }

        return [new $type($value)];
    }
}
