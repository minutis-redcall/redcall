<?php

namespace App\Tests\Base;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
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
        $client->disableReboot();
        $client->loginUser($user, $firewall);
    }
}
