<?php

namespace Bundles\PaginationBundle\Pagerfanta\View;

use Pagerfanta\View\TwitterBootstrap4View;
use RuntimeException;

class BootstrapLightView extends TwitterBootstrap4View
{
    public function getName()
    {
        return 'bootstrap_light';
    }

    protected function createDefaultTemplate()
    {
        throw new RuntimeException('Template should be injected using dependancy injection.');
    }
}
