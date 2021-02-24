<?php

namespace Bundles\ApiBundle\Base;

use Bundles\ApiBundle\Error\ViolationError;
use Bundles\ApiBundle\Exception\ApiException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class BaseController extends AbstractController
{
    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            'validator' => ValidatorInterface::class,
        ]);
    }

    public function validate($value, array $constraints)
    {
        $violations = $this->get('validator')->validate($value, $constraints);

        if (count($violations)) {
            throw new ApiException(
                new ViolationError($violations)
            );
        }
    }
}