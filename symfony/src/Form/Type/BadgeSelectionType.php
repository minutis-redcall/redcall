<?php

namespace App\Form\Type;

use App\Entity\Badge;
use App\Manager\BadgeManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;

class BadgeSelectionType extends AbstractType
{
    /**
     * @var BadgeManager
     */
    private $badgeManager;

    public function __construct(BadgeManager $badgeManager)
    {
        $this->badgeManager = $badgeManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('visible', EntityType::class, [
                'label'         => 'manage_volunteers.form.visible_badges',
                'class'         => Badge::class,
                'choice_label'  => function (Badge $badge) {
                    return $badge->getFullName();
                },
                'expanded'      => true,
                'multiple'      => true,
                'query_builder' => $this->badgeManager->getPublicBadgesQueryBuilder(),
            ])
            ->add('invisible', BadgeWidgetType::class, [
                'label'          => 'manage_volunteers.form.invisible_badges',
                'required'       => false,
                'multiple'       => true,
                'only_invisible' => true,
            ]);

        $builder->addModelTransformer(new CallbackTransformer(
            function ($fromModel) {
                $visibles   = [];
                $invisibles = [];
                foreach ($fromModel as $badge) {
                    /** @var Badge $badge */
                    if ($badge->isVisible()) {
                        $visibles[] = $badge;
                    } else {
                        $invisibles[] = $badge;
                    }
                }

                return [
                    'visible'   => $visibles,
                    'invisible' => $invisibles,
                ];
            },
            function (array $fromView) {
                return array_merge(
                    $fromView['visible'],
                    $fromView['invisible']
                );
            }
        ));
    }
}