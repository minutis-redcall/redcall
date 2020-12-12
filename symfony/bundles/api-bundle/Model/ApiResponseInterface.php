<?php

namespace Bundles\ApiBundle\Model;

interface ApiResponseInterface extends \JsonSerializable
{
    public function getPayload() : array;
}