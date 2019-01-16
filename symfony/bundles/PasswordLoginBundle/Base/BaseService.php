<?php

namespace Bundles\PasswordLoginBundle\Base;

use Bundles\PasswordLoginBundle\Traits\ServiceTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

abstract class BaseService implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    use ServiceTrait;
}
