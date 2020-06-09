<?php

namespace Bundles\ApiBundle\Manager;

use Bundles\ApiBundle\Repository\TokenLogRepository;

class TokenLogManager
{
    /**
     * @var TokenLogRepository
     */
    private $tokenLogRepository;

    /**
     * @param TokenLogRepository $tokenLogRepository
     */
    public function __construct(TokenLogRepository $tokenLogRepository)
    {
        $this->tokenLogRepository = $tokenLogRepository;
    }


}