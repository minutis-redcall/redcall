<?php

namespace App\Form\Type;

use App\Manager\BadgeManager;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class BadgeWidgetType extends TextType
{
    /**
     * @var BadgeManager
     */
    private $badgeManager;

    /**
     * @param BadgeManager $badgeManager
     */
    public function __construct(BadgeManager $badgeManager)
    {
        $this->badgeManager = $badgeManager;
    }

    public function getBlockPrefix()
    {
        return 'badge_widget';
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if ($view->vars['value']) {
            $badge = $this->badgeManager->find($view->vars['value']);
            if ($badge) {
                $view->vars['data'] = [$badge->toSearchResults()];
            }
        }
    }
}