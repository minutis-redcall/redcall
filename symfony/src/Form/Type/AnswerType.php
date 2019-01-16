<?php

namespace App\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;

class AnswerType extends TextType
{
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'answer';
    }
}
