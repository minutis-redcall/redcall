<?php

namespace Bundles\ApiBundle\Manager;

use Bundles\ApiBundle\Repository\TokenRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;

class TokenManager
{
    /**
     * @var TokenRepository
     */
    private $tokenRepository;

    /**
     * @var Security
     */
    private $security;

    public function getTokensQueryBuilderForUser() : QueryBuilder
    {
        return $this->tokenRepository->getTokensQueryBuilderForUser(
            $this->security->getUser()->getUsername()
        );
    }
}