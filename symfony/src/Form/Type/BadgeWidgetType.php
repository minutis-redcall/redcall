<?php

namespace App\Form\Type;

use App\Entity\Badge;
use App\Manager\BadgeManager;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
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

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->addModelTransformer(
            new CallbackTransformer(
                function (?Badge $badge) {
                    return $badge ? $badge->getId() : null;
                },
                function (?int $badgeId) {
                    return $badgeId ? $this->badgeManager->find($badgeId) : null;
                }
            )
        );
    }

    public function getBlockPrefix()
    {
        return 'badge_widget';
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if ($view->vars['value']) {
            $badge = $view->vars['value'];
            if (!$badge instanceof Badge) {
                $badge = $this->badgeManager->find($view->vars['value']);
                if ($badge) {
                    $view->vars['data'] = [$badge->toSearchResults()];
                }
            }
        }
    }
}