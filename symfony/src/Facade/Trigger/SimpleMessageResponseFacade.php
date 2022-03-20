<?php

namespace App\Facade\Trigger;

use Bundles\ApiBundle\Annotation as Api;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class SimpleMessageResponseFacade implements FacadeInterface
{
    /**
     * @var int
     */
    protected $numberOfTriggeredVolunteers;

    /**
     * @var string
     */
    protected $triggerUrl;

    public function __construct(int $numberOfTriggeredVolunteers, string $triggerUrl)
    {
        $this->numberOfTriggeredVolunteers = $numberOfTriggeredVolunteers;
        $this->triggerUrl                  = $triggerUrl;
    }

    static public function getExample(Api\Facade $decorates = null) : FacadeInterface
    {
        $facade = new self(42, 'https://rcl.re/campaign/1234');

        return $facade;
    }

    public function getNumberOfTriggeredVolunteers() : int
    {
        return $this->numberOfTriggeredVolunteers;
    }

    public function getTriggerUrl() : string
    {
        return $this->triggerUrl;
    }
}