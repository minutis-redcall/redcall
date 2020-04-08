<?php

namespace Bundles\TwilioBundle\Service;

use Bundles\TwilioBundle\Entity\TwilioMessage;
use Bundles\TwilioBundle\Event\TwilioMessageEvent;
use Bundles\TwilioBundle\Manager\TwilioCallManager;
use Bundles\TwilioBundle\Manager\TwilioMessageManager;
use Bundles\TwilioBundle\TwilioEvents;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\RouterInterface;
use Twilio\Rest\Client;

class Twilio
{
    /**
     * @var Client|null
     */
    private $client;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var TwilioMessageManager
     */
    private $messageManager;

    /**
     * @var TwilioCallManager
     */
    private $callManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param RouterInterface          $router
     * @param EventDispatcherInterface $eventDispatcher
     * @param TwilioMessageManager     $messageManager
     * @param TwilioCallManager        $callManager
     * @param LoggerInterface|null     $logger
     */
    public function __construct(RouterInterface $router, EventDispatcherInterface $eventDispatcher, TwilioMessageManager $messageManager, TwilioCallManager $callManager, LoggerInterface $logger = null)
    {
        $this->router = $router;
        $this->eventDispatcher = $eventDispatcher;
        $this->messageManager = $messageManager;
        $this->callManager = $callManager;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * Send an SMS to the given phone number and store outbound information
     * on the database for further tracking.
     *
     * @param string $phoneNumber
     * @param string $message
     * @param array  $context
     *
     * @return TwilioMessage
     *
     * @throws \Exception
     */
    public function sendMessage(string $phoneNumber, string $message, array $context = []): TwilioMessage
    {
        $entity = new TwilioMessage();
        $entity->setUuid(Uuid::uuid4());
        $entity->setDirection(TwilioMessage::DIRECTION_OUTBOUND);
        $entity->setMessage($message);
        $entity->setFromNumber(getenv('TWILIO_NUMBER'));
        $entity->setToNumber($phoneNumber);
        $entity->setContext($context);

        try {
            $outbound = $this->getClient()->messages->create(sprintf('+%s', $phoneNumber), [
                'from'           => sprintf('+%s', getenv('TWILIO_NUMBER')),
                'body'           => $message,
                'statusCallback' => trim(getenv('WEBSITE_URL'), '/').$this->router->generate('twilio_status', ['uuid' => $entity->getUuid()]),
            ]);

            $entity->setSid($outbound->sid);
            $entity->setStatus($outbound->status);

            $this->eventDispatcher->dispatch(new TwilioMessageEvent($entity), TwilioEvents::MESSAGE_SENT);
        } catch (\Exception $e) {
            $entity->setStatus('error');

            $this->logger->error('Unable to send SMS', [
                'phoneNumber' => $entity->getToNumber(),
                'context' => $context,
                'exception' => $e,
            ]);
        }

        $this->messageManager->save($entity);

        return $entity;
    }

    public function fetchPrices(int $retries)
    {
  //      $this->fetchMessagePrices($retries);
        $this->fetchCallPrices($retries);
    }

    /**
     * @param int $retries
     *
     * @throws \Twilio\Exceptions\ConfigurationException
     * @throws \Twilio\Exceptions\TwilioException
     */
    public function fetchMessagePrices(int $retries)
    {
        $entities = $this->messageManager->findMessagesWithoutPrice($retries);
        foreach ($entities as $entity) {
            $message = $this->getClient()->messages($entity->getSid())->fetch();
            if ($message->price) {
                $entity->setPrice($message->price);
                $entity->setUnit($message->priceUnit);
                $this->eventDispatcher->dispatch(new TwilioMessageEvent($entity), TwilioEvents::MESSAGE_PRICE_UPDATED);
            } else {
                $entity->setRetry($entity->getRetry() + 1);
            }

            $this->messageManager->save($entity);
            usleep(500000);
        }
    }

    /**
     * Handles an incoming message
     *
     * @param string $sid
     *
     * @throws \Twilio\Exceptions\ConfigurationException
     * @throws \Twilio\Exceptions\TwilioException
     */
    public function handleInboundMessage(string $sid)
    {
        $inbound = $this->getClient()->messages($sid)->fetch();

        $entity = new TwilioMessage();
        $entity->setUuid(Uuid::uuid4());
        $entity->setDirection(TwilioMessage::DIRECTION_INBOUND);
        $entity->setMessage($inbound->body);
        $entity->setFromNumber(ltrim($inbound->from, '+'));
        $entity->setToNumber(ltrim($inbound->to, '+'));
        $entity->setSid($inbound->sid);
        $entity->setStatus($inbound->status);
        $entity->setPrice($inbound->price);
        $entity->setUnit($inbound->priceUnit);

        // Required to create the TwilioMessage id
        $this->messageManager->save($entity);

        $this->eventDispatcher->dispatch(new TwilioMessageEvent($entity), TwilioEvents::MESSAGE_RECEIVED);
        $this->messageManager->save($entity);
    }

    /**
     * @param int $retries
     *
     * @throws \Twilio\Exceptions\ConfigurationException
     * @throws \Twilio\Exceptions\TwilioException
     */
    public function fetchCallPrices(int $retries)
    {
        $entities = $this->callManager->findCallsWithoutPrice($retries);
        foreach ($entities as $entity) {
            $entity->setRetry($entity->getRetry() + 1);

            try {
                $message = $this->getClient()->calls($entity->getSid())->fetch();
            } catch (\Exception $e) {
                $this->logger->error('Unable to fetch Twilio call', [
                    'id' => $entity->getId(),
                    'sid' => $entity->getSid(),
                    'exception' => $e->getMessage(),
                ]);

                $this->callManager->save($entity);

                continue;
            }
            if ($message->startTime) {
                $entity->setStartedAt($message->startTime);
            }
            if ($message->endTime) {
                $entity->setEndedAt($message->endTime);
            }
            if ($message->duration) {
                $entity->setDuration($message->duration);
            }
            if ($message->price) {
                $entity->setPrice($message->price);
                $entity->setUnit($message->priceUnit);
                $this->eventDispatcher->dispatch(new TwilioMessageEvent($entity), TwilioEvents::CALL_PRICE_UPDATED);
            }

            $this->callManager->save($entity);
            usleep(500000);
        }
    }

    /**
     * @return Client
     * @throws \Twilio\Exceptions\ConfigurationException
     */
    private function getClient()
    {
        if ($this->client) {
            return $this->client;
        }

        $this->client = new Client(getenv('TWILIO_ACCOUNT_SID'), getenv('TWILIO_AUTH_TOKEN'));

        return $this->client;
    }
}