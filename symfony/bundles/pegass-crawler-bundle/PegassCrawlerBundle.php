<?php

namespace Bundles\PegassCrawlerBundle;

use LogicException;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class PegassCrawlerBundle extends Bundle
{
    public function boot()
    {
        if (!getenv('PEGASS_LOGIN')) {
            throw new LogicException('You should set the PEGASS_LOGIN environment variable.');
        }

        if (!getenv('PEGASS_PASSWORD')) {
            throw new LogicException('You should set the PEGASS_PASSWORD environment variable.');
        }
    }
}
