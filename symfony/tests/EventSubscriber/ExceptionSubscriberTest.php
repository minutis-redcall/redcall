<?php

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\ExceptionSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;

class ExceptionSubscriberTest extends TestCase
{
    public function testGetSubscribedEventsReturnsExceptionEvent()
    {
        $events = ExceptionSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::EXCEPTION, $events);
        $this->assertSame([['logException', 0]], $events[KernelEvents::EXCEPTION]);
    }

    public function testLogExceptionDoesNothingInTestEnvironment()
    {
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->method('getEnvironment')->willReturn('test');

        $subscriber = new ExceptionSubscriber($kernel);

        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $request    = Request::create('/');
        $exception  = new \RuntimeException('Test exception');

        $event = new ExceptionEvent($httpKernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        // In non-prod, logException should simply return without calling Bootstrap
        // If it tried to call Bootstrap in test, it would fail, so no exception = success
        $subscriber->logException($event);

        // If we reach here, the method did not try to call Google Bootstrap
        $this->assertTrue(true);
    }

    public function testLogExceptionDoesNothingInDevEnvironment()
    {
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->method('getEnvironment')->willReturn('dev');

        $subscriber = new ExceptionSubscriber($kernel);

        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $request    = Request::create('/');
        $exception  = new \RuntimeException('Test exception');

        $event = new ExceptionEvent($httpKernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $subscriber->logException($event);

        $this->assertTrue(true);
    }

    public function testConstructorAcceptsKernel()
    {
        $kernel = $this->createMock(KernelInterface::class);

        $subscriber = new ExceptionSubscriber($kernel);

        $this->assertInstanceOf(ExceptionSubscriber::class, $subscriber);
    }

    public function testImplementsEventSubscriberInterface()
    {
        $kernel = $this->createMock(KernelInterface::class);
        $subscriber = new ExceptionSubscriber($kernel);

        $this->assertInstanceOf(EventSubscriberInterface::class, $subscriber);
    }

    public function testSubscribedEventsHaveCorrectPriority()
    {
        $events = ExceptionSubscriber::getSubscribedEvents();

        // The listener is registered with priority 0
        $listeners = $events[KernelEvents::EXCEPTION];
        $this->assertSame('logException', $listeners[0][0]);
        $this->assertSame(0, $listeners[0][1]);
    }
}
