<?php

namespace Bundles\TwilioBundle\Manager;

use Bundles\TwilioBundle\Entity\TwilioStatus;
use Bundles\TwilioBundle\Repository\TwilioStatusRepository;

class TwilioStatusManager
{
    /**
     * @var TwilioStatusRepository
     */
    private $statusRepository;

    /**
     * @param TwilioStatusRepository $statusRepository
     */
    public function __construct(TwilioStatusRepository $statusRepository)
    {
        $this->statusRepository = $statusRepository;
    }

    public function save(TwilioStatus $status)
    {
        $this->statusRepository->save($status);
    }
}