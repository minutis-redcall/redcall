<?php

namespace App\Issue;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

abstract class IssueLogger
{
    const SEVERITY_MINOR = 'minor';
    const SEVERITY_MAJOR = 'major';
    const SEVERITY_CRITICAL = 'critical';

    /** @var LoggerInterface */
    private $logger;

    /**
     * EventLogger constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string     $message
     * @param \Throwable $exception
     * @param string     $severity
     * @param array      $context
     */
    public function fileIssueFromException(string $message, \Throwable $exception, $severity = self::SEVERITY_MAJOR, array $context = [])
    {
        $this->logger->log($this->fromIssueSeverityToLogLevel($severity), "Filed a $severity issue", array_merge($context, [
            'message' => $message,
            'exception' => $exception,
        ]));

        $this->doFileIssueFromException($message, $exception, $severity, $context);
    }

    /**
     * @param string     $message
     * @param \Throwable $exception
     * @param string     $severity
     * @param array      $context
     */
    abstract protected function doFileIssueFromException(string $message, \Throwable $exception, string $severity, array $context = []);


    /**
     * @param string $severity
     *
     * @return string
     */
    private function fromIssueSeverityToLogLevel(string $severity): string
    {
        switch ($severity) {
            case self::SEVERITY_MAJOR;
                return LogLevel::ERROR;
            case self::SEVERITY_CRITICAL;
                return LogLevel::CRITICAL;
            case self::SEVERITY_MINOR:
            default:
                return LogLevel::INFO;
        }
    }
}