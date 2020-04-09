<?php

namespace Bundles\SandboxBundle\Provider;

use App\Provider\Call\CallProvider;

class FakeCallProvider implements CallProvider
{
    /**
     * {@inheritdoc}
     */
    public function send(string $phoneNumber, array $context = []): ?string
    {
        // TODO: Implement send() method.
    }
}