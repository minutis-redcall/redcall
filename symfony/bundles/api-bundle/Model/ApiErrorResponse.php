<?php

namespace Bundles\ApiBundle\Model;

use Bundles\ApiBundle\Enum\AuthenticationError;

class ApiErrorResponse extends ApiResponse
{
    /**
     * @var AuthenticationError
     */
    private $error;

    /* ErrorFacade */
    public function __construct(AuthenticationError $error, array $payload = [])
    {
        $this->error = $error;

        parent::__construct($payload, $error->getStatus());
    }

    public function jsonSerialize()
    {
        return [
            'success' => false,
            'code'    => $this->error->getCode(),
            'message' => $this->error->getMessage(),
            'payload' => $this->getPayload(),
        ];
    }
}