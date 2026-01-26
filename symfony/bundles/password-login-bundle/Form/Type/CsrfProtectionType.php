<?php

namespace Bundles\PasswordLoginBundle\Form\Type;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Because framework.yaml declares "csrf_protection: false" in tests,
 * this extension makes sure the 'csrf_protection' option is always defined,
 * preventing a 500 error when forms are created without explicitly setting this option.
 */
class CsrfProtectionType extends AbstractTypeExtension
{
    public static function getExtendedTypes() : iterable
    {
        return [FormType::class];
    }

    public function configureOptions(OptionsResolver $resolver) : void
    {
        $resolver->setDefined('csrf_protection');
    }
}