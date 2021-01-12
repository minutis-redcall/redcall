<?php

namespace App\Form\Type;

use App\Entity\Badge;
use App\Manager\BadgeManager;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
                function ($badge) use ($options) {
                    if (!$options['multiple']) {
                        if (!$badge) {
                            return null;
                        }

                        if ($options['only_invisible'] && $badge->isVisible()) {
                            return null;
                        }

                        return $badge->getId();
                    }

                    if ($badge instanceof Collection) {
                        $badge = $badge->toArray();
                    }

                    $badges = $badge;
                    if ($options['only_invisible']) {
                        $badges = array_filter($badge, function (Badge $badge) {
                            return !$badge->isVisible();
                        });
                    }

                    return implode(',', array_map(function (Badge $badge) {
                        return $badge->getId();
                    }, $badges));
                },
                function ($badgeId) use ($options) {
                    if (!$options['multiple']) {
                        return $badgeId ? $this->badgeManager->find($badgeId) : null;
                    }

                    return $badgeId ? array_map(function (int $badgeId) {
                        return $this->badgeManager->find($badgeId);
                    }, explode(',', $badgeId)) : [];
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
        $view->vars['multiple'] = $options['multiple'];
        if ($view->vars['value']) {
            $badge = $view->vars['value'];
            if (!$badge instanceof Badge) {
                if (!$options['multiple']) {
                    $badge = $this->badgeManager->find($view->vars['value']);
                    if ($badge && (!$options['only_invisible'] || !$badge->isVisible())) {
                        $view->vars['data'] = [$badge->toSearchResults()];
                    }
                } else {
                    $view->vars['data'] = [];
                    foreach (explode(',', $view->vars['value']) as $badgeId) {
                        $badge = $this->badgeManager->find($badgeId);
                        if ($badge && (!$options['only_invisible'] || !$badge->isVisible())) {
                            $view->vars['data'][] = $badge->toSearchResults();
                        }
                    }
                }
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'multiple'       => false,
            'only_invisible' => false,
        ]);
    }
}