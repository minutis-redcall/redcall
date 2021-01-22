<?php

namespace Bundles\ApiBundle\Error;

use Bundles\ApiBundle\Contracts\ErrorInterface;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Bundles\ApiBundle\Model\Facade\CollectionFacade;
use Bundles\ApiBundle\Model\Facade\ViolationFacade;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ViolationError implements ErrorInterface
{
    /**
     * @var ConstraintViolationListInterface
     */
    private $violations;

    public function __construct(ConstraintViolationListInterface $violations)
    {
        $this->violations = $violations;
    }

    public function getStatus() : int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getCode() : string
    {
        return '0003';
    }

    public function getMessage() : string
    {
        return 'The provided payload contains property violations.';
    }

    public function getContext() : FacadeInterface
    {
        $collection = new CollectionFacade();

        foreach ($this->violations as $violation) {
            $collection[] = new ViolationFacade($violation);
        }

        return $collection;
    }
}