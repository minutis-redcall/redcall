<?php

namespace Bundles\PaginationBundle\Pagerfanta\Template;

use Pagerfanta\View\Template\TwitterBootstrap4Template;
use Symfony\Component\Translation\TranslatorInterface;

class BootstrapLightTemplate extends TwitterBootstrap4Template
{
    public function __construct(TranslatorInterface $translator)
    {
        parent::__construct();

        $this->setOptions([
            'prev_message'        => $translator->trans('pagination.prev'),
            'next_message'        => $translator->trans('pagination.next'),
            'css_container_class' => 'pagination mx-auto',
        ]);
    }
}
