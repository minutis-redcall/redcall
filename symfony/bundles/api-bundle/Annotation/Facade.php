<?php

namespace Bundles\ApiBundle\Annotation;

use Bundles\ApiBundle\Contracts\FacadeInterface;

/**
 * @Annotation
 * @Target({"ANNOTATION"})
 */
final class Facade
{
    /**
     * @var string
     */
    private $class;

    /**
     * @var self|null
     */
    private $decorates;

    public function __construct(array $data)
    {
        $this->class = $data['class'] ?? $data['value'] ?? null;

        if (!is_subclass_of($this->class, FacadeInterface::class)) {
            throw new \InvalidArgumentException(
                sprintf('@Facade only take instances of "%s" in "class" property.', FacadeInterface::class)
            );
        }

        $this->decorates = $data['decorates'] ?? null;

        if ($this->decorates && !($this->decorates instanceof self)) {
            throw new \InvalidArgumentException(
                sprintf('@Facade only take instances of "%s" in "decorates" property.', __CLASS__)
            );
        }
    }

    public function getClass() : string
    {
        return $this->class;
    }

    public function setClass(string $class) : Facade
    {
        $this->class = $class;

        return $this;
    }

    public function getDecorates() : ?Facade
    {
        return $this->decorates;
    }

    public function setDecorates(?Facade $decorates) : Facade
    {
        $this->decorates = $decorates;

        return $this;
    }
}