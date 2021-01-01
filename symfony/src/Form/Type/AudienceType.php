<?php

namespace App\Form\Type;

use App\Entity\Badge;
use App\Entity\Structure;
use App\Manager\UserManager;
use App\Repository\BadgeRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AudienceType extends AbstractType
{
    /**
     * @var UserManager
     */
    private $userManager;

    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('structures', EntityType::class, [
                'class'         => Structure::class,
                'query_builder' => $this->userManager->getCurrentUserStructuresQueryBuilder(),
                'choice_label'  => function (Structure $structure) {
                    return $structure->getDisplayName();
                },
                'multiple'      => true,
                'expanded'      => true,
            ])
            ->add('tags', EntityType::class, [
                'class'         => Badge::class,
                'query_builder' => function (BadgeRepository $tagRepository) {
                    return $tagRepository->getPublicBadgesQueryBuilder();
                },
                'choice_label'  => function (Badge $badge) {
                    return $badge->getName();
                },
                'multiple'      => true,
                'expanded'      => true,
            ])
            ->add('nivols', TextareaType::class, [
                'label'    => false,
                'required' => false,
            ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
    }

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label'          => false,
            'error_bubbling' => false,
        ]);
    }

    public function getBlockPrefix() : string
    {
        return 'audience';
    }
}