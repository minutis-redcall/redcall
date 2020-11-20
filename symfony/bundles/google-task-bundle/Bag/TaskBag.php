<?php

namespace Bundles\GoogleTaskBundle\Bag;

use Bundles\GoogleTaskBundle\Api\TaskInterface;

class TaskBag
{
    private $tasks;

    public function addTask(TaskInterface $task)
    {
        $this->tasks[get_class($task)] = $task;
    }

    public function getTask(string $name) : TaskInterface
    {
        if (!array_key_exists($name, $this->tasks)) {
            throw new \LogicException(sprintf('Task %s does not exist', $name));
        }

        return $this->tasks[$name];
    }
}
