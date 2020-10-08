<?php

namespace App\Component\HttpFoundation;

use Symfony\Component\HttpFoundation\Response;

class NoContentResponse extends Response
{
    public function __construct(array $headers = [])
    {
        parent::__construct('', Response::HTTP_NO_CONTENT, $headers);
    }
}
