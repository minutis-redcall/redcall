<?php

namespace App\Model;

use App\Tools\Encryption;

class MinutisToken
{
    /**
     * @var string
     */
    private $accessToken;

    /**
     * @var int
     */
    private $accessTokenExpiresAt;

    static public function unserialize(string $cypher) : self
    {
        $token = new self;

        foreach (json_decode(Encryption::decrypt($cypher), true) as $property => $value) {
            $token->{$property} = $value;
        }

        return $token;
    }

    public function getAccessToken() : string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken) : MinutisToken
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function getAccessTokenExpiresAt() : int
    {
        return $this->accessTokenExpiresAt;
    }

    public function setAccessTokenExpiresAt(int $accessTokenExpiresAt) : MinutisToken
    {
        $this->accessTokenExpiresAt = $accessTokenExpiresAt;

        return $this;
    }

    public function isAccessTokenExpired() : bool
    {
        return $this->accessTokenExpiresAt < time();
    }

    public function serialize() : string
    {
        return Encryption::encrypt(json_encode(get_object_vars($this)));
    }

    public function __toString() : string
    {
        return $this->accessToken;
    }
}