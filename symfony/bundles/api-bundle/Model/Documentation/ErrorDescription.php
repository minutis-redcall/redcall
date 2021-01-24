<?php

namespace Bundles\ApiBundle\Model\Documentation;

class ErrorDescription
{
    /**
     * @var int
     */
    private $status;

    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $description;

    public function getStatus() : int
    {
        return $this->status;
    }

    public function setStatus(int $status) : ErrorDescription
    {
        $this->status = $status;

        return $this;
    }

    public function getCode() : string
    {
        return $this->code;
    }

    public function setCode(string $code) : ErrorDescription
    {
        $this->code = $code;

        return $this;
    }

    public function getDescription() : string
    {
        return $this->description;
    }

    public function setDescription(string $description) : ErrorDescription
    {
        $this->description = $description;

        return $this;
    }
}