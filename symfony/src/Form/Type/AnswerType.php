<?php

namespace App\Form\Type;

use App\Entity\Choice;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class AnswerType extends TextType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'attr'        => [
                'maxlength' => Choice::LENGTH,
            ],
            'constraints' => [
                new Length([
                    'min' => 1,
                    'max' => Choice::LENGTH,
                ]),
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'answer';
    }
}
