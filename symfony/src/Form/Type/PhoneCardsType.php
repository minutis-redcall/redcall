<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PhoneCardsType extends AbstractType
{
    public function getParent()
    {
        return CollectionType::class;
    }

    public function getBlockPrefix()
    {
        return 'phone_cards';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label'         => false,
            'entry_type'    => PhoneCardType::class,
            'entry_options' => [
                'label'    => false,
                'required' => false,
            ],
            'allow_add'     => true,
            'allow_delete'  => true,
            'delete_empty'  => true,
            'prototype'     => true,
            'required'      => false,
            'by_reference'  => false,
        ]);
    }
}