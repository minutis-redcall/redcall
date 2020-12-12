<?php

namespace Bundles\ApiBundle\Model;

use Bundles\ApiBundle\Enum\Error;

class ApiErrorResponse extends ApiResponse
{
    /**
     * @var Error
     */
    private $error;

    public function __construct(Error $error, array $payload = [])
    {
        $this->error = $error;

        parent::__construct($payload, $error->getStatus());
    }

    public function jsonSerialize()
    {
        return [
            'success' => false,
            'code'    => $this->error->getCode(),
            'error'   => $this->error,
            'message' => $this->error->getMessage(),
            'payload' => $this->getPayload(),
        ];
    }
}