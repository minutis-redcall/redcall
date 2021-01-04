<?php

namespace Bundles\ChartBundle\Bag;

use Bundles\ChartBundle\ContextType\ContextTypeInterface;

class ContextTypeBag
{
    private $contextTypes = [];

    public function addContextType(ContextTypeInterface $contextType)
    {
        $this->contextTypes[get_class($contextType)] = $contextType;
    }

    public function getContextType(string $name) : ContextTypeInterface
    {
        if (!array_key_exists($name, $this->contextTypes)) {
            throw new \LogicException(sprintf('Task %s does not exist', $name));
        }

        return $this->contextTypes[$name];
    }

    public function getContextTypes() : array
    {
        return $this->contextTypes;
    }
}
