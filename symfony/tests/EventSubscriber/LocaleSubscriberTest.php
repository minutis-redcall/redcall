<?php

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\LocaleSubscriber;
use App\Manager\LocaleManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class LocaleSubscriberTest extends TestCase
{
    private $localeManager;
    private $subscriber;

    protected function setUp() : void
    {
        $this->localeManager = $this->createMock(LocaleManager::class);
        $this->subscriber    = new LocaleSubscriber($this->localeManager);
    }

    public function testImplementsEventSubscriberInterface()
    {
        $this->assertInstanceOf(EventSubscriberInterface::class, $this->subscriber);
    }

    public function testGetSubscribedEventsReturnsExpectedEvents()
    {
        $events = LocaleSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::REQUEST, $events);
        $this->assertArrayHasKey(SecurityEvents::INTERACTIVE_LOGIN, $events);
    }

    public function testKernelRequestEventHasPriority16()
    {
        $events = LocaleSubscriber::getSubscribedEvents();

        // Format: [['methodName', priority]]
        $this->assertSame([['onKernelRequest', 16]], $events[KernelEvents::REQUEST]);
    }

    public function testInteractiveLoginEventRegistersCorrectMethod()
    {
        $events = LocaleSubscriber::getSubscribedEvents();

        $this->assertSame('onInteractiveLogin', $events[SecurityEvents::INTERACTIVE_LOGIN]);
    }

    public function testOnKernelRequestRestoresFromSessionForNonApiPath()
    {
        $request = Request::create('/dashboard');

        $event = $this->createMock(RequestEvent::class);
        $event->method('getRequest')->willReturn($request);

        $this->localeManager->expects($this->once())->method('restoreFromSession');

        $this->subscriber->onKernelRequest($event);
    }

    public function testOnKernelRequestSkipsApiPaths()
    {
        $request = Request::create('/api/v1/volunteers');

        $event = $this->createMock(RequestEvent::class);
        $event->method('getRequest')->willReturn($request);

        $this->localeManager->expects($this->never())->method('restoreFromSession');

        $this->subscriber->onKernelRequest($event);
    }

    public function testOnInteractiveLoginRestoresFromUserForNonApiPath()
    {
        $request = Request::create('/login');
        $token   = $this->createMock(TokenInterface::class);

        $event = new InteractiveLoginEvent($request, $token);

        $this->localeManager->expects($this->once())->method('restoreFromUser');

        $this->subscriber->onInteractiveLogin($event);
    }

    public function testOnInteractiveLoginSkipsApiPaths()
    {
        $request = Request::create('/api/auth');
        $token   = $this->createMock(TokenInterface::class);

        $event = new InteractiveLoginEvent($request, $token);

        $this->localeManager->expects($this->never())->method('restoreFromUser');

        $this->subscriber->onInteractiveLogin($event);
    }

    public function testOnKernelRequestHandlesRootPath()
    {
        $request = Request::create('/');

        $event = $this->createMock(RequestEvent::class);
        $event->method('getRequest')->willReturn($request);

        $this->localeManager->expects($this->once())->method('restoreFromSession');

        $this->subscriber->onKernelRequest($event);
    }

    public function testOnKernelRequestHandlesApiExactPath()
    {
        $request = Request::create('/api');

        $event = $this->createMock(RequestEvent::class);
        $event->method('getRequest')->willReturn($request);

        $this->localeManager->expects($this->never())->method('restoreFromSession');

        $this->subscriber->onKernelRequest($event);
    }

    public function testOnKernelRequestHandlesApiFalsePositive()
    {
        // Path that starts with /api but is not an API path (/application)
        $request = Request::create('/application/settings');

        $event = $this->createMock(RequestEvent::class);
        $event->method('getRequest')->willReturn($request);

        // strpos('/application/settings', '/api') === 0 is FALSE
        // so locale should be restored
        $this->localeManager->expects($this->once())->method('restoreFromSession');

        $this->subscriber->onKernelRequest($event);
    }
}
