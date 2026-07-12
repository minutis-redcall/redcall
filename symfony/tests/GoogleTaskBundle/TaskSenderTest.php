<?php

namespace App\Tests\GoogleTaskBundle;

use Bundles\GoogleTaskBundle\Bag\TaskBag;
use Bundles\GoogleTaskBundle\Service\TaskSender;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;

class TaskSenderTest extends TestCase
{
    protected function tearDown() : void
    {
        putenv('GOOGLE_TASK_PROCESS');

        parent::tearDown();
    }

    public function testDefaultProcessIsAppEngineWhenEnvIsNotSet()
    {
        putenv('GOOGLE_TASK_PROCESS');

        $this->assertTrue($this->createSender()->getDefaultProcess()->isAppEngine());
    }

    public function testDefaultProcessIsHttpWhenEnvSaysSo()
    {
        putenv('GOOGLE_TASK_PROCESS=http');

        $this->assertTrue($this->createSender()->getDefaultProcess()->isHttp());
    }

    public function testDefaultProcessIsAppEngineForUnknownValues()
    {
        putenv('GOOGLE_TASK_PROCESS=whatever');

        $this->assertTrue($this->createSender()->getDefaultProcess()->isAppEngine());
    }

    private function createSender() : TaskSender
    {
        return new TaskSender(
            $this->createMock(RouterInterface::class),
            $this->createMock(KernelInterface::class),
            $this->createMock(TaskBag::class)
        );
    }
}
