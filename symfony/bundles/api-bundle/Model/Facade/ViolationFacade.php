<?php

namespace Bundles\ApiBundle\Model\Facade;

use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Symfony\Component\Validator\ConstraintViolation;

class ViolationFacade implements FacadeInterface
{
    /**
     * @var string
     */
    private $propertyPath;

    /**
     * @var string|null
     */
    private $invalidValue;

    /**
     * @var string
     */
    private $message;

    public function __construct(ConstraintViolation $violation)
    {
        $this->propertyPath = $violation->getPropertyPath();
        $this->invalidValue = $violation->getInvalidValue();
        $this->message      = $violation->getMessage();
    }

    static public function getExample(Facade $decorates = null) : FacadeInterface
    {
        return new EmptyFacade();
    }

    public function getPropertyPath() : ?string
    {
        return $this->propertyPath;
    }

    public function getInvalidValue() : ?string
    {
        return $this->invalidValue;
    }

    public function getMessage() : string
    {
        return $this->message;
    }
}
