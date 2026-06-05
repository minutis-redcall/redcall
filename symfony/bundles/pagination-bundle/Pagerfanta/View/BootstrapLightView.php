<?php

namespace Bundles\PaginationBundle\Pagerfanta\View;

use Pagerfanta\View\Template\TemplateInterface;
use Pagerfanta\View\TwitterBootstrap4View;

class BootstrapLightView extends TwitterBootstrap4View
{
    public function getName(): string
    {
        return 'bootstrap_light';
    }

    protected function createDefaultTemplate(): TemplateInterface
    {
        throw new \RuntimeException('Template should be injected using dependancy injection.');
    }
}
