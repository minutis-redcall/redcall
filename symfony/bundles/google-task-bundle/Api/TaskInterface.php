<?php

namespace Bundles\GoogleTaskBundle\Api;

interface TaskInterface
{
    /**
     * Will be used by the task receiver service to execute your asynchronous task.
     *
     * @param array $context parameter passed when using the task sender service.
     */
    public function execute(array $context);

    /**
     * Should contain the queue name on Google side; you'll usually use getenv('SOME_ENV_VAR')
     * in order to have this value in the env parameters.
     *
     * @return string
     */
    public function getQueueName() : string;
}