<?php

namespace Bundles\ApiBundle\Model\Facade;

use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class CollectionFacade extends \ArrayObject implements FacadeInterface
{
    static public function getExample(Facade $decorates = null) : FacadeInterface
    {
        if (null === $decorates) {
            throw new \LogicException('This facade decorates another facade');
        }

        $child = $decorates->class;

        $facade   = [];
        $facade[] = $child::getExample($decorates->decorates);
        $facade[] = $child::getExample($decorates->decorates);

        return $facade;
    }

    public function offsetSet($key, $value)
    {
        if ($this->count() > 0) {
            $first = $this->offsetGet(array_key_first($this->getArrayCopy()));

            if (gettype($first) !== gettype($value)) {
                throw new \InvalidArgumentException(sprintf('Cannot add a "%s" to a collection of "%s".', gettype($value), gettype($first)));
            }

            if (is_object($first) && get_class($first) !== get_class($value)) {
                throw new \InvalidArgumentException(sprintf('Cannot add a "%s" to a collection of "%s".', get_class($value), get_class($first)));
            }
        }

        if (!is_subclass_of($value, FacadeInterface::class)) {
            throw new \InvalidArgumentException(sprintf('A "%s" can only contain "%s" instances.', CollectionFacade::class, FacadeInterface::class));
        }

        parent::offsetSet($key, $value);
    }

    public function first() : ?FacadeInterface
    {
        $key = array_key_first($this->getArrayCopy());

        if (null === $key) {
            return null;
        }

        return $this->offsetGet($key);
    }
}