<?php

namespace App\Issue;

use Psr\Log\LoggerInterface;
use Sentry\SentryBundle\SentrySymfonyClient;

class SentryIssueLogger extends IssueLogger
{
    /** @var SentrySymfonyClient */
    private $client;

    /**
     * SentryEventLogger constructor.
     *
     * @param LoggerInterface     $logger
     * @param SentrySymfonyClient $client
     */
    public function __construct(LoggerInterface $logger, SentrySymfonyClient $client)
    {
        parent::__construct($logger);

        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFileIssueFromException(string $message, \Throwable $exception, string $severity, array $context = [])
    {
        $this->client->captureException($exception, [
            'message' => $message,
            'level' => $this->fromIssueSeverityToSentryLevel($severity),
            'extra' => $context,
        ]);
    }

    /**
     * @param string $severity
     *
     * @return string
     */
    private function fromIssueSeverityToSentryLevel(string $severity): string
    {
        switch ($severity) {
            case self::SEVERITY_MAJOR;
                return 'error';
            case self::SEVERITY_CRITICAL;
                return 'fatal';
            case self::SEVERITY_MINOR:
            default:
                return 'warning';
        }
    }
}