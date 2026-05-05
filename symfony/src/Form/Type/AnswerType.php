<?php

namespace App\Form\Type;

use App\Entity\Choice;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class AnswerType extends TextType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'attr'        => [
                'maxlength' => Choice::MAX_LENGTH_DEFAULT,
            ],
            'constraints' => [
                new Length([
                    'min' => 1,
                    'max' => Choice::MAX_LENGTH_DEFAULT,
                ]),
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'answer';
    }
}
