<?php

namespace App\Pagerfanta\View;

use Pagerfanta\View\TwitterBootstrap4View;

class LightView extends TwitterBootstrap4View
{
    public function getName()
    {
        return 'light';
    }

    protected function createDefaultTemplate()
    {
        throw new \RuntimeException('Template should be injected using dependancy injection.');
    }
}
