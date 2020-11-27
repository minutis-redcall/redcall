<?php

namespace App\Form\Type;

use App\Entity\Phone;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PhoneCardType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('e164', PhoneType::class, [
                'label'    => false,
                'required' => false,
            ])
            ->add('preferred', CheckboxType::class, [
                'label'    => false,
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => Phone::class]);
    }

    public function getBlockPrefix()
    {
        return 'phone_card';
    }
}