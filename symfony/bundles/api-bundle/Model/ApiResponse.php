<?php

namespace Bundles\ApiBundle\Model;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiResponse extends JsonResponse implements ApiResponseInterface
{
    private $payload;

    public function __construct(array $payload = [], int $status = Response::HTTP_OK)
    {
        $this->payload = $payload;

        parent::__construct($this, $status);
    }

    public function jsonSerialize()
    {
        return [
            'success' => true,
            'payload' => $this->getPayload(),
        ];
    }

    public function getPayload() : array
    {
        return $this->payload;
    }
}
