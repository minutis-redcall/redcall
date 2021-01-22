<?php

namespace Bundles\ApiBundle\Model\Facade;

use Symfony\Component\Validator\ConstraintViolation;

class ViolationFacade
{
    /**
     * @var string
     */
    private $propertyPath;

    /**
     * @var string
     */
    private $invalidValue;

    /**
     * @var string
     */
    private $message;

    /**
     * @var array
     */
    private $parameters;

    public function __construct(ConstraintViolation $violation)
    {
        $this->propertyPath = $violation->getPropertyPath();
        $this->invalidValue = $violation->getInvalidValue();
        $this->message      = $violation->getMessage();
        $this->parameters   = $violation->getParameters();
    }

    public function getPropertyPath() : ?string
    {
        return $this->propertyPath;
    }

    public function getInvalidValue() : string
    {
        return $this->invalidValue;
    }

    public function getMessage() : string
    {
        return $this->message;
    }
}
