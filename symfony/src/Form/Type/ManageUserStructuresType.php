<?php

namespace App\Form\Type;

use App\Entity\User;
use App\Manager\UserManager;
use App\Security\Helper\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ManageUserStructuresType extends AbstractType
{
    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var Security
     */
    private $security;

    public function __construct(UserManager $userManager, Security $security)
    {
        $this->userManager = $userManager;
        $this->security    = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('structures', UserStructuresType::class, [
                'multiple' => true,
                'expanded' => true,
                'label'    => 'admin.pegass.delete_structures',
                'user'     => $options['user'],
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
            'user'       => $this->security->getUser(),
        ]);
    }
}