<?php

namespace Bundles\TwilioBundle\Manager;

use Bundles\TwilioBundle\Entity\TwilioMessage;
use Bundles\TwilioBundle\Event\TwilioMessageEvent;
use Bundles\TwilioBundle\Repository\TwilioMessageRepository;
use Bundles\TwilioBundle\Service\Twilio;
use Bundles\TwilioBundle\TwilioEvents;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twilio\Rest\Client;
use Twilio\TwiML\MessagingResponse;

class TwilioMessageManager
{
    /**
     * @var TwilioMessageRepository
     */
    private $messageRepository;

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

    public function __construct(TwilioMessageRepository $messageRepository,
        Twilio $twilio,
        EventDispatcherInterface $eventDispatcher,
        RouterInterface $router,
        LoggerInterface $logger = null)
    {
        $this->messageRepository = $messageRepository;
        $this->twilio            = $twilio;
        $this->eventDispatcher   = $eventDispatcher;
        $this->router            = $router;
        $this->logger            = $logger ?: new NullLogger();
    }

    public function get(string $uuid) : ?TwilioMessage
    {
        return $this->messageRepository->findOneByUuid($uuid);
    }

    public function getBySid(string $sid) : ?TwilioMessage
    {
        return $this->messageRepository->findOneBySid($sid);
    }

    /**
     * @param TwilioMessage $outbound
     */
    public function save(TwilioMessage $outbound)
    {
        $this->messageRepository->save($outbound);
    }

    public function handleInboundMessage(array $parameters) : ?MessagingResponse
    {
        $entity = new TwilioMessage();
        $entity->setUuid(Uuid::uuid4());
        $entity->setDirection(TwilioMessage::DIRECTION_INBOUND);
        $entity->setMessage($parameters['Body']);
        $entity->setFromNumber($parameters['From']);
        $entity->setToNumber($parameters['To']);
        $entity->setSid($parameters['MessageSid']);

        // Required to create the TwilioMessage id
        $this->messageRepository->save($entity);

        $event = new TwilioMessageEvent($entity);
        $this->eventDispatcher->dispatch($event, TwilioEvents::MESSAGE_RECEIVED);
        $this->messageRepository->save($entity);

        return $event->getResponse();
    }

    public function sendMessage(string $from,
        string $to,
        string $message,
        array $context = [],
        array $options = []) : TwilioMessage
    {
        if (!array_key_exists('messageUuid', $options)) {
            $options['messageUuid'] = Uuid::uuid4();
        }

        if (!array_key_exists('statusCallback', $options)) {
            $options['statusCallback'] = sprintf(
                '%s%s',
                trim(getenv('WEBSITE_URL'), '/'),
                $this->router->generate('twilio_status', ['uuid' => $options['messageUuid']])
            );
        }

        $entity = new TwilioMessage();
        $entity->setUuid($options['messageUuid']);
        $entity->setDirection(TwilioMessage::DIRECTION_OUTBOUND);
        $entity->setMessage($message);
        $entity->setFromNumber($from);
        $entity->setToNumber($to);
        $entity->setContext($context);

        try {
            $outbound = $this->getClient()->messages->create($to, [
                'from'           => $from,
                'body'           => $message,
                'statusCallback' => $options['statusCallback'],
            ]);

            $entity->setSid($outbound->sid);
            $entity->setStatus($outbound->status);

            $this->eventDispatcher->dispatch(new TwilioMessageEvent($entity), TwilioEvents::MESSAGE_SENT);
        } catch (\Exception $e) {
            $entity->setStatus('error');
            $entity->setError($e->getMessage());

            $this->eventDispatcher->dispatch(new TwilioMessageEvent($entity), TwilioEvents::MESSAGE_ERROR);

            $this->logger->error('Unable to send SMS', [
                'phoneNumber' => $entity->getToNumber(),
                'context'     => $context,
                'exception'   => $e->getMessage(),
            ]);
        }

        $this->messageRepository->save($entity);

        return $entity;
    }

    public function fetchPrices(int $retries)
    {
        $entities = $this->messageRepository->findEntitiesWithoutPrice($retries);
        foreach ($entities as $entity) {

            try {
                $message = $this->getClient()->messages($entity->getSid())->fetch();
            } catch (\Exception $e) {
                $this->logger->error('Unable to fetch Twilio message', [
                    'id'        => $entity->getId(),
                    'sid'       => $entity->getSid(),
                    'exception' => $e->getMessage(),
                ]);

                $this->messageRepository->save($entity);

                continue;
            }

            if ($message->status) {
                $entity->setStatus($message->status);
            }
            if ($message->price) {
                $entity->setPrice($message->price);
                $entity->setUnit($message->priceUnit);
                $this->eventDispatcher->dispatch(new TwilioMessageEvent($entity), TwilioEvents::MESSAGE_PRICE_UPDATED);
            } else {
                $entity->setRetry($entity->getRetry() + 1);
            }

            $this->messageRepository->save($entity);
            usleep(500000);
        }
    }

    public function foreach(callable $callback)
    {
        $this->messageRepository->foreach($callback);
    }

    private function getClient() : Client
    {
        return $this->twilio->getClient();
    }
}