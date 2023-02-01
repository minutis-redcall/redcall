<?php

namespace App\Model;

class OAuthUser
{
    /**
     * @var string
     */
    private $email;

    /**
     * @var string|null
     */
    private $firstname = null;

    /**
     * @var string|null
     */
    private $lastname = null;

    /**
     * @var string|null
     */
    private $pictureUrl = null;

    public function getEmail() : string
    {
        return $this->email;
    }

    public function setEmail(string $email) : self
    {
        $this->email = $email;

        return $this;
    }

    public function getFirstname() : ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname) : self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname() : ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname) : self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getPictureUrl() : ?string
    {
        return $this->pictureUrl;
    }

    public function setPictureUrl(?string $pictureUrl) : self
    {
        $this->pictureUrl = $pictureUrl;

        return $this;
    }

    public function hasProfileInfo() : bool
    {
        return $this->firstname || $this->lastname || $this->pictureUrl;
    }
}