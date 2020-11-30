<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TelType;

class PhoneType extends AbstractType
{
    public function getBlockPrefix()
    {
        return 'phone';
    }

    public function getParent()
    {
        return TelType::class;
    }
}