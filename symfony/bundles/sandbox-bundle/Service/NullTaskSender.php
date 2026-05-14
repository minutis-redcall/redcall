<?php

namespace Bundles\SandboxBundle\Service;

use Bundles\GoogleTaskBundle\Bag\TaskBag;
use Bundles\GoogleTaskBundle\Enum\Process;
use Bundles\GoogleTaskBundle\Service\TaskSender;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Test-env replacement for Bundles\GoogleTaskBundle\Service\TaskSender.
 *
 * The real sender executes the task inline in any non-prod environment
 * (see TaskSender::fire). That's fine in dev where the underlying tasks
 * hit fake providers, but in the test env several admin routes
 * (/admin/maintenance/pegass-files, /admin/maintenance/annuaire-national)
 * fire tasks whose execute() calls Google APIs that fail without a
 * service-account key. We just record the dispatch and return.
 */
class NullTaskSender extends TaskSender
{
    /**
     * @var array<int, array{name: string, context: array}>
     */
    private array $dispatched = [];

    public function __construct(RouterInterface $router, KernelInterface $kernel, TaskBag $taskBag)
    {
        parent::__construct($router, $kernel, $taskBag);
    }

    public function fire(string $name, array $context = [], ?Process $process = null)
    {
        $this->dispatched[] = ['name' => $name, 'context' => $context];
    }

    /**
     * @return array<int, array{name: string, context: array}>
     */
    public function getDispatched(): array
    {
        return $this->dispatched;
    }
}
