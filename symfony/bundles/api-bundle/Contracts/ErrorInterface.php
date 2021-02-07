<?php

namespace Bundles\ApiBundle\Contracts;

interface ErrorInterface
{
    public function getStatus() : int;

    public function getCode() : string;

    public function getMessage() : string;

    public function getContext() : FacadeInterface;
}