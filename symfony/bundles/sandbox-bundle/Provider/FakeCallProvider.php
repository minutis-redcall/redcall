<?php

namespace Bundles\SandboxBundle\Provider;

use App\Manager\MessageManager;
use App\Provider\Call\CallProvider;
use Bundles\SandboxBundle\Entity\FakeCall;
use Bundles\SandboxBundle\Manager\FakeCallManager;
use Bundles\TwilioBundle\Entity\TwilioCall;
use Bundles\TwilioBundle\Event\TwilioCallEvent;
use Bundles\TwilioBundle\TwilioEvents;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class FakeCallProvider implements CallProvider
{
    /**
     * @var MessageManager
     */
    private $messageManager;

    /**
     * @var FakeCallManager
     */
    private $fakeCallManager;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @param MessageManager      $messageManager
     * @param FakeCallManager     $fakeCallManager
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(MessageManager $messageManager, FakeCallManager $fakeCallManager, EventDispatcherInterface $dispatcher)
    {
        $this->messageManager = $messageManager;
        $this->fakeCallManager = $fakeCallManager;
        $this->dispatcher = $dispatcher;
    }

    public function send(string $phoneNumber, array $context = []): ?string
    {
        $this->triggerHook($phoneNumber, $context, TwilioEvents::CALL_ESTABLISHED, FakeCall::TYPE_ESTABLISH);

        return 'ok';
    }

    public function triggerHook(string $phoneNumber, array $context, string $eventType, string $hookType, string $keyPressed = null)
    {
        $call = new TwilioCall();
        $call->setContext($context);

        $event = new TwilioCallEvent($call, $keyPressed);
        $this->dispatcher->dispatch($event, $eventType);

        $domxml = new \DOMDocument('1.0');
        $domxml->preserveWhiteSpace = false;
        $domxml->formatOutput = true;
        $domxml->loadXML($event->getResponse()->asXML());

        $fakeCall = new FakeCall();
        $fakeCall->setType($hookType);
        $fakeCall->setPhoneNumber($phoneNumber);
        $fakeCall->setMessageId($context['message_id']);
        $fakeCall->setContent($domxml->saveXML());
        $fakeCall->setCreatedAt(new \DateTime());

        $this->fakeCallManager->save($fakeCall);
    }
}