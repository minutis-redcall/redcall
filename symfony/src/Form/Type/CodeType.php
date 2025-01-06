<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class CodeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('code', TextType::class, [
                'label'       => 'Veuillez saisir le code reçu par email',
                'required'    => true,
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 6, 'max' => 6]),
                ],
            ])
            ->add('_remember_me', CheckboxType::class, [
                'label'    => 'Faire confiance à cet appareil et laisser la session ouverte',
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Connexion',
            ]);
    }

    public function getBlockPrefix()
    {
        return '';
    }
}
