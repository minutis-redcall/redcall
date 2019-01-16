<?php

namespace App\Pagerfanta\Template;

use Pagerfanta\View\Template\TwitterBootstrap4Template;
use Symfony\Component\Translation\TranslatorInterface;

class LightTemplate extends TwitterBootstrap4Template
{
    public function __construct(TranslatorInterface $translator)
    {
        parent::__construct();

        $this->setOptions([
            'prev_message'        => $translator->trans('base.pager.prev'),
            'next_message'        => $translator->trans('base.pager.next'),
            'css_container_class' => 'pagination mx-auto',
        ]);
    }
}
