<?php

namespace Bundles\SandboxBundle\Provider;

use App\Entity\Message;
use App\Manager\MessageManager;
use App\Provider\Call\CallProvider;
use App\Services\MessageFormatter;

class FakeCallProvider implements CallProvider
{
    /**
     * @var MessageManager
     */
    private $messageManager;

    /**
     * @var FakeSmsProvider
     */
    private $fakeSmsProvider;

    /**
     * @var MessageFormatter
     */
    private $formatter;

    /**
     * @param MessageManager   $messageManager
     * @param FakeSmsProvider  $fakeSmsProvider
     * @param MessageFormatter $formatter
     */
    public function __construct(MessageManager $messageManager, FakeSmsProvider $fakeSmsProvider, MessageFormatter $formatter)
    {
        $this->messageManager = $messageManager;
        $this->fakeSmsProvider = $fakeSmsProvider;
        $this->formatter = $formatter;
    }

    /**
     * {@inheritdoc}
     */
    public function send(string $phoneNumber, array $context = []): ?string
    {
        /** @var Message $message */
        $message = $this->messageManager->find($context['message_id']);

        $body = $this->formatter->formatSMSContent($message);

        $this->fakeSmsProvider->send($phoneNumber, $body);

        return 'ok';
    }
}