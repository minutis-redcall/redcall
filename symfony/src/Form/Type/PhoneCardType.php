<?php

namespace App\Form\Type;

use App\Entity\Phone;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PhoneCardType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('editor', TelType::class, [
                'label'    => false,
                'required' => false,
                'mapped'   => false,
            ])
            ->add('e164', HiddenType::class, [
                'label'    => false,
                'required' => false,
            ])
            ->add('preferred', CheckboxType::class, [
                'label'    => false,
                'required' => false,
            ]);

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            if (!$event->getData()) {
                return;
            }

            /** @var Phone $phone */
            $phone = $event->getData();

            $event->getForm()->get('editor')->setData(
                $phone->getE164()
            );
        });
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