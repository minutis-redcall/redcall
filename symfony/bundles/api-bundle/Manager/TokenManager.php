<?php

namespace Bundles\ApiBundle\Manager;

use Bundles\ApiBundle\Repository\TokenRepository;

class TokenManager
{
    /**
     * @var TokenRepository
     */
    private $tokenRepository;

    /**
     * @param TokenRepository $tokenRepository
     */
    public function __construct(TokenRepository $tokenRepository)
    {
        $this->tokenRepository = $tokenRepository;
    }


}