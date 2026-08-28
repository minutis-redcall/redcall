<?php

namespace App\Tests\GoogleTaskBundle;

use Bundles\GoogleTaskBundle\Bag\TaskBag;
use Bundles\GoogleTaskBundle\Service\TaskSender;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;

class TaskSenderTest extends TestCase
{
    /**
     * @var string|false
     */
    private $originalWebsiteUrl;

    protected function setUp() : void
    {
        parent::setUp();

        $this->originalWebsiteUrl = getenv('WEBSITE_URL');
    }

    protected function tearDown() : void
    {
        putenv('GOOGLE_TASK_PROCESS');
        putenv(false === $this->originalWebsiteUrl ? 'WEBSITE_URL' : 'WEBSITE_URL='.$this->originalWebsiteUrl);

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

    public function testHttpTargetUrlUsesWebsiteUrlRatherThanRequestContext()
    {
        putenv('WEBSITE_URL=https://redcall.example.org');

        $sender = $this->createSender('/cloud-task', 'http://attacker-or-plain-http-host/cloud-task');

        $this->assertSame('https://redcall.example.org/cloud-task', $sender->getHttpTargetUrl());
    }

    public function testHttpTargetUrlSupportsTrailingSlashInWebsiteUrl()
    {
        putenv('WEBSITE_URL=https://redcall.example.org/');

        $sender = $this->createSender('/cloud-task', 'http://whatever/cloud-task');

        $this->assertSame('https://redcall.example.org/cloud-task', $sender->getHttpTargetUrl());
    }

    public function testHttpTargetUrlFallsBackToRequestContextWithoutWebsiteUrl()
    {
        putenv('WEBSITE_URL');

        $sender = $this->createSender('/cloud-task', 'http://127.0.0.1:8000/cloud-task');

        $this->assertSame('http://127.0.0.1:8000/cloud-task', $sender->getHttpTargetUrl());
    }

    private function createSender(?string $relativeUrl = null, ?string $absoluteUrl = null) : TaskSender
    {
        $router = $this->createStub(RouterInterface::class);

        if (null !== $relativeUrl) {
            $router->method('generate')->willReturnCallback(
                function (string $route, array $parameters = [], int $referenceType = RouterInterface::ABSOLUTE_PATH) use ($relativeUrl, $absoluteUrl) {
                    return RouterInterface::ABSOLUTE_URL === $referenceType ? $absoluteUrl : $relativeUrl;
                }
            );
        }

        return new TaskSender(
            $router,
            $this->createStub(KernelInterface::class),
            $this->createStub(TaskBag::class)
        );
    }
}
