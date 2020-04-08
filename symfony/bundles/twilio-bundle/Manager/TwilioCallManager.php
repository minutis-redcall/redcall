<?php

namespace Bundles\TwilioBundle\Manager;

use Bundles\TwilioBundle\Entity\TwilioCall;
use Bundles\TwilioBundle\Event\TwilioCallEvent;
use Bundles\TwilioBundle\Repository\TwilioCallRepository;
use Bundles\TwilioBundle\TwilioEvents;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twilio\TwiML\VoiceResponse;

class TwilioCallManager
{
    /**
     * @var TwilioCallRepository
     */
    private $callRepository;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @param TwilioCallRepository     $callRepository
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(TwilioCallRepository $callRepository, EventDispatcherInterface $eventDispatcher)
    {
        $this->callRepository = $callRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param string $uuid
     *
     * @return TwilioCall|null
     */
    public function get(string $uuid): ?TwilioCall
    {
        return $this->callRepository->findOneByUuid($uuid);
    }

    /**
     * @param TwilioCall $outbound
     */
    public function save(TwilioCall $outbound)
    {
        $this->callRepository->save($outbound);
    }

    /**
     * @param int $retries
     *
     * @return TwilioCall[]
     */
    public function findCallsWithoutPrice(int $retries): array
    {
        return $this->callRepository->findEntitiesWithoutPrice($retries);
    }

    /**
     * @param array $parameters
     *
     * @return VoiceResponse|null
     *
     * @throws \Exception
     */
    public function handleIncomingCall(array $parameters): ?VoiceResponse
    {
        $entity = new TwilioCall();
        $entity->setUuid(Uuid::uuid4());
        $entity->setDirection(TwilioCall::DIRECTION_INBOUND);
        $entity->setFromNumber(ltrim($parameters['From'], '+'));
        $entity->setToNumber(ltrim($parameters['To'], '+'));
        $entity->setSid($parameters['CallSid']);
        $entity->setStatus($parameters['CallStatus']);

        // Required to create the TwilioCall id
        $this->save($entity);

        $event = new TwilioCallEvent($entity);
        $this->eventDispatcher->dispatch($event, TwilioEvents::CALL_RECEIVED);

        if ($event->getResponse()) {
            $entity->setMessage($event->getResponse()->asXML());
        }

        $this->save($entity);

        return $event->getResponse();
    }
}