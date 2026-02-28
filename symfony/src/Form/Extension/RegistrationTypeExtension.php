<?php

namespace App\Form\Extension;

use App\Entity\User;
use Bundles\PasswordLoginBundle\Form\Type\RegistrationType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class RegistrationTypeExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes() : iterable
    {
        return [
            RegistrationType::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            /** @var User $user */
            $user = $event->getData();

            $user->setLocale('fr');
            $user->setTimezone('Europe/Paris');
        });
    }
}