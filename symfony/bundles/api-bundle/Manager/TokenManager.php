<?php

namespace Bundles\ApiBundle\Manager;

use Bundles\ApiBundle\Entity\Token;
use Bundles\ApiBundle\Repository\TokenRepository;
use Bundles\ApiBundle\Util;
use Doctrine\ORM\QueryBuilder;
use Ramsey\Uuid\Uuid;
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

    public function __construct(TokenRepository $tokenRepository, Security $security)
    {
        $this->tokenRepository = $tokenRepository;
        $this->security        = $security;
    }

    public function getTokensQueryBuilderForUser() : QueryBuilder
    {
        return $this->tokenRepository->getTokensQueryBuilderForUser(
            $this->security->getUser()->getUsername()
        );
    }

    public function findTokenByNameForUser(string $tokenName) : ?Token
    {
        return $this->tokenRepository->findTokenByNameForUser(
            $this->security->getUser()->getUsername(),
            $tokenName
        );
    }

    public function createTokenForUser(string $tokenName)
    {
        $username = $this->security->getUser()->getUsername();

        $token = new Token();
        $token->setName($tokenName);
        $token->setUsername($username);
        $token->setToken(Uuid::uuid4());
        $token->setSecret(Util::encrypt(Util::generate(Token::CLEARTEXT_SECRET_LENGTH), $username));
        $token->setCreatedAt(new \DateTime());

        $this->tokenRepository->save($token);
    }

    public function remove(Token $token)
    {
        $this->tokenRepository->remove($token);
    }

}