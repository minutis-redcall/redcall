<?php

namespace Bundles\PasswordLoginBundle\Base;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

abstract class BaseCommand extends ContainerAwareCommand
{
    protected function get(string $service)
    {
        return $this->getContainer()->get($service);
    }

    protected function getParameter(string $parameter)
    {
        return $this->getContainer()->getParameter($parameter);
    }

    protected function getManager($manager = null)
    {
        $em = $this
            ->get('doctrine')
            ->getManager();

        if (!is_null($manager)) {
            return $em->getRepository($manager);
        }

        return $em;
    }
}
