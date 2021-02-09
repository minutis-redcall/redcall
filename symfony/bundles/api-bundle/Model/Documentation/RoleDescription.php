<?php

namespace Bundles\ApiBundle\Model\Documentation;

class RoleDescription
{
    /**
     * @var string
     */
    private $attribute;

    /**
     * @var string|null
     */
    private $subject;

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
}