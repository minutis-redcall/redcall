<?php

namespace App\Form\Type;

use App\Entity\Structure;
use App\Entity\User;
use App\Manager\UserManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserStructuresType extends AbstractType
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
                'query_builder' => $this->userManager->getUserStructuresQueryBuilder($options['user']),
                'choice_label'  => function (Structure $structure) {
                    return $structure->getDisplayName();
                },
                'multiple'      => true,
                'expanded'      => true,
                'label'         => 'admin.pegass.delete_structures',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'base.button.delete',
                'attr'  => [
                    'class' => 'btn-danger',
                ],
            ]);

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'label'      => false,
            'user'       => null,
        ]);
    }
}