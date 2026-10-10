<?php

namespace Bundles\GoogleTaskBundle\Service;

use Bundles\GoogleTaskBundle\Bag\TaskBag;
use Bundles\GoogleTaskBundle\Enum\Process;
use Bundles\GoogleTaskBundle\Security\Signer;
use Google\Cloud\Tasks\V2\AppEngineHttpRequest;
use Google\Cloud\Tasks\V2\Client\CloudTasksClient;
use Google\Cloud\Tasks\V2\CreateTaskRequest;
use Google\Cloud\Tasks\V2\HttpMethod;
use Google\Cloud\Tasks\V2\HttpRequest;
use Google\Cloud\Tasks\V2\Task;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;

class TaskSender
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var TaskBag
     */
    private $taskBag;

    /**
     * @var CloudTasksClient
     */
    private $taskClient;

    public function __construct(RouterInterface $router,
        KernelInterface $kernel,
        TaskBag $taskBag)
    {
        $this->router  = $router;
        $this->kernel  = $kernel;
        $this->taskBag = $taskBag;
    }

    public function fire(string $name, array $context = [], ?Process $process = null)
    {
        // If you want to test your task through Cloud Tasks using ngrok on development environment,
        // pass Process::HTTP() in $process.
        if (null === $process && 'prod' !== $this->kernel->getEnvironment()) {
            $this->taskBag->getTask($name)->execute($context);

            return;
        }

        if (null === $process) {
            $process = $this->getDefaultProcess();
        }

        $payload = json_encode([
            'name'      => $name,
            'context'   => $context,
            'signature' => Signer::sign($name, $context),
        ]);

        switch ($process) {
            case Process::APP_ENGINE():
                $cloudTask = $this->createAppEngineTask($payload);
                break;
            case Process::HTTP():
                $cloudTask = $this->createHttpTask($payload);
                break;
        }

        $this->getClient()->createTask(
            CreateTaskRequest::build($this->getQueueName($name), $cloudTask)
        );
    }

    public function getDefaultProcess() : Process
    {
        // On Cloud Run, tasks must use HTTP targets; App Engine targets
        // only work when the app runs on GAE.
        if ('http' === getenv('GOOGLE_TASK_PROCESS')) {
            return Process::HTTP();
        }

        return Process::APP_ENGINE();
    }

    private function createAppEngineTask(string $payload) : Task
    {
        $httpRequest = new AppEngineHttpRequest();

        $uri = $this->router->generate('google_task_receiver');

        $httpRequest->setRelativeUri($uri)
                    ->setHttpMethod(HttpMethod::POST)
                    ->setBody($payload);

        $task = new Task();
        $task->setAppEngineHttpRequest($httpRequest);

        return $task;
    }

    public function getHttpTargetUrl() : string
    {
        // The task URL must not depend on the scheme/host of the request that
        // fired it: a visitor browsing over plain http would produce an
        // http:// target that Cloud Tasks cannot reach when the load balancer
        // only exposes https. WEBSITE_URL is the canonical public base URL.
        $base = getenv('WEBSITE_URL');
        if (!$base) {
            return $this->router->generate('google_task_receiver', [], RouterInterface::ABSOLUTE_URL);
        }

        return rtrim($base, '/').$this->router->generate('google_task_receiver');
    }

    private function createHttpTask(string $payload) : Task
    {
        $httpRequest = new HttpRequest();

        $url = $this->getHttpTargetUrl();

        $httpRequest->setUrl($url)
                    ->setHttpMethod(HttpMethod::POST)
                    ->setBody($payload);

        $task = new Task();
        $task->setHttpRequest($httpRequest);

        return $task;
    }

    private function getClient() : CloudTasksClient
    {
        if (!$this->taskClient) {
            $this->taskClient = new CloudTasksClient();
        }

        return $this->taskClient;
    }

    private function getQueueName(string $name) : string
    {
        return $this->getClient()->queueName(
            getenv('GCP_PROJECT_NAME'),
            getenv('GCP_PROJECT_LOCATION'),
            $this->taskBag->getTask($name)->getQueueName()
        );
    }
}
