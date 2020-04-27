<?php

namespace Bundles\SandboxBundle\Provider;

use App\Manager\MessageManager;
use App\Provider\Call\CallProvider;
use Bundles\SandboxBundle\Entity\FakeCall;
use Bundles\SandboxBundle\Manager\FakeCallManager;
use Bundles\TwilioBundle\Entity\TwilioCall;
use Bundles\TwilioBundle\Event\TwilioCallEvent;
use Bundles\TwilioBundle\Manager\TwilioCallManager;
use Bundles\TwilioBundle\TwilioEvents;
use Ramsey\Uuid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class FakeCallProvider implements CallProvider
{
    /**
     * @var MessageManager
     */
    private $messageManager;

    /**
     * @var TwilioCallManager
     */
    private $twilioCallManager;

    /**
     * @var FakeCallManager
     */
    private $fakeCallManager;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @param MessageManager           $messageManager
     * @param TwilioCallManager        $twilioCallManager
     * @param FakeCallManager          $fakeCallManager
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(MessageManager $messageManager, TwilioCallManager $twilioCallManager, FakeCallManager $fakeCallManager, EventDispatcherInterface $dispatcher)
    {
        $this->messageManager = $messageManager;
        $this->twilioCallManager = $twilioCallManager;
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
        $call->setUuid(Uuid::uuid4());
        $call->setDirection(TwilioCall::DIRECTION_OUTBOUND);
        $call->setFromNumber($phoneNumber);
        $call->setToNumber($phoneNumber);
        $call->setContext($context);
        $this->twilioCallManager->save($call);

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

        return $fakeCall;
    }
}