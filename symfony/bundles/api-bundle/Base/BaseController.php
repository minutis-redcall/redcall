<?php

namespace Bundles\ApiBundle\Base;

use App\Security\Helper\Security;
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
            Security::class,
        ]);
    }

    protected function validate($value, array $constraints, array $groups = ['Default'])
    {
        $violations = $this->get('validator')->validate($value, $constraints, $groups);

        if (count($violations)) {
            throw new ApiException(
                new ViolationError($violations)
            );
        }
    }

    protected function getPlatform() : ?string
    {
        return $this->getSecurity()->getPlatform();
    }

    protected function getSecurity() : Security
    {
        return $this->get(Security::class);
    }
}