<?php

namespace Bundles\ApiBundle\Base;

use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

abstract class BaseService implements ServiceSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @required
     */
    public function setContainer(ContainerInterface $container) : ?ContainerInterface
    {
        $previous        = $this->container;
        $this->container = $container;

        return $previous;
    }

    public function get(string $serviceName)
    {
        return $this->container->get($serviceName);
    }
}