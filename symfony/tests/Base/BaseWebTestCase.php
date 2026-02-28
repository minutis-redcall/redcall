<?php

namespace App\Tests\Base;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;

abstract class BaseWebTestCase extends WebTestCase
{
    /**
     * @template T
     *
     * @param class-string<T>|string $id
     *
     * @return T
     */
    protected function get(string $id)
    {
        return $this->getContainer()->get($id);
    }

    protected function login(
        KernelBrowser $client,
        UserInterface $user,
        string $firewall = 'main'
    ) : void {
        // Do NOT reboot the kernel or you lose the session
        $client->disableReboot();

        $container = static::getContainer();
        $session   = $container->get('session');

        // Create an authenticated token
        $token = new UsernamePasswordToken(
            $user,
            null,
            $firewall,
            $user->getRoles()
        );

        // Store token in token storage
        $container->get('security.token_storage')->setToken($token);

        // Store token in the session (this is the important part)
        $session->set('_security_'.$firewall, serialize($token));
        $session->save();

        // Attach session to the browser
        $client->getCookieJar()->set(
            new Cookie($session->getName(), $session->getId())
        );
    }
}