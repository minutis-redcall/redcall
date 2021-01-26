<?php

namespace Bundles\ApiBundle\Model\Documentation;

class RoleDescription
{
    /**
     * @var string|null
     */
    private $method;

    /**
     * @var string
     */
    private $attribute;

    /**
     * @var string|null
     */
    private $subject;

    /**
     * @var string|null
     */
    private $channel;

    public function getMethod() : ?string
    {
        return $this->method;
    }

    public function setMethod(?string $method) : RoleDescription
    {
        $this->method = $method;

        return $this;
    }

    public function getAttribute() : string
    {
        return $this->attribute;
    }

    public function setAttribute(string $attribute) : RoleDescription
    {
        $this->attribute = $attribute;

        return $this;
    }

    public function getSubject() : ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject) : RoleDescription
    {
        $this->subject = $subject;

        return $this;
    }

    public function getChannel() : ?string
    {
        return $this->channel;
    }

    public function setChannel(?string $channel) : RoleDescription
    {
        $this->channel = $channel;

        return $this;
    }
}