<?php

namespace Bundles\TwilioBundle\Manager;

use Bundles\TwilioBundle\Entity\TwilioCall;
use Bundles\TwilioBundle\Event\TwilioCallEvent;
use Bundles\TwilioBundle\Repository\TwilioCallRepository;
use Bundles\TwilioBundle\Service\Twilio;
use Bundles\TwilioBundle\TwilioEvents;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\RouterInterface;
use Twilio\Rest\Client;
use Twilio\TwiML\VoiceResponse;

class TwilioCallManager
{
    /**
     * @var TwilioCallRepository
     */
    private $callRepository;

    /**
     * @var Twilio
     */
    private $twilio;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var LoggerInterface|null
     */
    private $logger;

    /**
     * @param TwilioCallRepository     $callRepository
     * @param Twilio                   $twilio
     * @param EventDispatcherInterface $eventDispatcher
     * @param RouterInterface          $router
     * @param LoggerInterface|null     $logger
     */
    public function __construct(TwilioCallRepository $callRepository, Twilio $twilio, EventDispatcherInterface $eventDispatcher, RouterInterface $router, LoggerInterface $logger = null)
    {
        $this->callRepository = $callRepository;
        $this->twilio = $twilio;
        $this->eventDispatcher = $eventDispatcher;
        $this->router = $router;
        $this->logger = $logger ?: new NullLogger();
    }

    public function get(string $uuid): ?TwilioCall
    {
        return $this->callRepository->findOneByUuid($uuid);
    }

    public function save(TwilioCall $call)
    {
        $this->callRepository->save($call);
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
        $this->callRepository->save($entity);

        $event = new TwilioCallEvent($entity);
        $this->eventDispatcher->dispatch($event, TwilioEvents::CALL_RECEIVED);

        if ($event->getResponse()) {
            $entity->setMessage($event->getResponse()->asXML());
        }

        $this->callRepository->save($entity);

        return $event->getResponse();
    }

    public function sendCall(string $phoneNumber, array $context = []): TwilioCall
    {
        $entity = new TwilioCall();
        $entity->setUuid(Uuid::uuid4());
        $entity->setDirection(TwilioCall::DIRECTION_OUTBOUND);
        $entity->setFromNumber(getenv('TWILIO_NUMBER'));
        $entity->setToNumber($phoneNumber);
        $entity->setContext($context);

        try {
            $outbound = $this->getClient()->calls->create(
                sprintf('+%s', $phoneNumber),
                sprintf('+%s', getenv('TWILIO_NUMBER')),
                [
                    'url' => trim(getenv('WEBSITE_URL'), '/').$this->router->generate('twilio_outgoing_call', [
                        'uuid' => $entity->getUuid(),
                    ])
                ]
            );

            $entity->setSid($outbound->sid);
            $entity->setStatus($outbound->status);

            $this->eventDispatcher->dispatch(new TwilioCallEvent($entity), TwilioEvents::CALL_INITIALIZED);
        } catch (\Exception $e) {
            $entity->setStatus('error');

            $this->logger->error('Unable to send call', [
                'phoneNumber' => $entity->getToNumber(),
                'context' => $context,
                'exception' => $e->getMessage(),
            ]);
        }

        $this->callRepository->save($entity);

        return $entity;
    }

    public function handleCallEstablished(TwilioCall $call): ?VoiceResponse
    {
        $event = new TwilioCallEvent($call);

        $this->eventDispatcher->dispatch($event, TwilioEvents::CALL_ESTABLISHED);

        $response = $event->getResponse();
        if ($response) {
            $call->setMessage($response->asXML());
        }

        $this->callRepository->save($call);

        return $response;
    }

    public function handleKeyPressed(TwilioCall $call, int $keyPressed): ?VoiceResponse
    {
        $event = new TwilioCallEvent($call, $keyPressed);

        $this->eventDispatcher->dispatch($event, TwilioEvents::CALL_KEY_PRESSED);

        $response = $event->getResponse();
        if ($response) {
            $call->setMessage($response->asXML());
        }

        $this->callRepository->save($call);

        return $response;
    }

    public function fetchPrices(int $retries)
    {
        $entities = $this->callRepository->findEntitiesWithoutPrice($retries);
        foreach ($entities as $entity) {
            $entity->setRetry($entity->getRetry() + 1);

            try {
                $call = $this->getClient()->calls($entity->getSid())->fetch();
            } catch (\Exception $e) {
                $this->logger->error('Unable to fetch Twilio call', [
                    'id' => $entity->getId(),
                    'sid' => $entity->getSid(),
                    'exception' => $e->getMessage(),
                ]);

                $this->callRepository->save($entity);

                continue;
            }

            if ($call->status) {
                $entity->setStatus($call->status);
            }
            if ($call->startTime) {
                $entity->setStartedAt($call->startTime);
            }
            if ($call->endTime) {
                $entity->setEndedAt($call->endTime);
            }
            if ($call->duration) {
                $entity->setDuration($call->duration);
            }
            if ($call->price) {
                $entity->setPrice($call->price);
                $entity->setUnit($call->priceUnit);
                $this->eventDispatcher->dispatch(new TwilioCallEvent($entity), TwilioEvents::CALL_PRICE_UPDATED);
            }

            $this->callRepository->save($entity);
            usleep(500000);
        }
    }

    public function foreach(callable $callback)
    {
        $this->callRepository->foreach($callback);
    }

    private function getClient(): Client
    {
        return $this->twilio->getClient();
    }
}