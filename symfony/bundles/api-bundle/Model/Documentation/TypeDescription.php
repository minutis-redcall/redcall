<?php

namespace Bundles\ApiBundle\Model\Documentation;

use Bundles\ApiBundle\Contracts\FacadeInterface;
use Symfony\Component\PropertyInfo\Type;

class TypeDescription extends Type
{
    public function isFacade() : bool
    {
        return null !== $this->getFacadeClass();
    }

    public function getFacadeClass() : ?string
    {
        // Is a facade
        if ($this->getClassName() && is_subclass_of($this->getClassName(), FacadeInterface::class)) {
            return $this->getClassName();
        }

        // Is a collection of facade
        if ($this->isCollection()) {
            $value = $this->getCollectionValueType();

            if ($value->getClassName() && is_subclass_of($value->getClassName(), FacadeInterface::class)) {
                return $value->getClassName();
            }
        }

        return null;
    }
}