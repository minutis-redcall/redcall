<?php

namespace Bundles\PasswordLoginBundle\Traits;

use Symfony\Component\EventDispatcher\Event;

trait ServiceTrait
{
    protected function get(string $service)
    {
        return $this->container->get($service);
    }

    protected function getParameter(string $parameter)
    {
        return $this->container->getParameter($parameter);
    }

    protected function isGranted($attributes, $subject = null): bool
    {
        return $this->get('security.authorization_checker')->isGranted($attributes, $subject);
    }

    protected function getUser()
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();
        if (is_scalar($user)) {
            return null;
        }

        return $user;
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

    protected function trans($property, array $parameters = [], ?string $domain = null, ?string $locale = null)
    {
        return $this->container->get('translator')->trans($property, $parameters, $domain, $locale);
    }

    protected function dispatch($eventName, Event $event = null)
    {
        return $this->get('event_dispatcher')->dispatch($eventName, $event);
    }
}