<?php

namespace Bundles\ApiBundle\Contracts;

interface ApiExceptionInterface
{
    public function getError() : ErrorInterface;
}
