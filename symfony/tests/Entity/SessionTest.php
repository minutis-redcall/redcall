<?php

namespace App\Tests\Entity;

use App\Entity\Session;
use PHPUnit\Framework\TestCase;

class SessionTest extends TestCase
{
    public function testSetLifetime(): void
    {
        $session = new Session();

        $result = $session->setLifetime(3600);

        $this->assertSame($session, $result);
        $this->assertSame(3600, $session->getLifetime());
    }

    public function testSetLifetimeZero(): void
    {
        $session = new Session();
        $session->setLifetime(0);

        $this->assertSame(0, $session->getLifetime());
    }

    public function testSetLifetimeLargeValue(): void
    {
        $session = new Session();
        $session->setLifetime(86400 * 365);

        $this->assertSame(86400 * 365, $session->getLifetime());
    }

    public function testSetLifetimeOverwritesPreviousValue(): void
    {
        $session = new Session();
        $session->setLifetime(100);
        $session->setLifetime(200);

        $this->assertSame(200, $session->getLifetime());
    }

    public function testGetLifetimeDefaultsToNull(): void
    {
        $session = new Session();

        $this->assertNull($session->getLifetime());
    }
}
