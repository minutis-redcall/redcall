<?php

namespace App\Form\Type;

use App\Form\Model\Campaign;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreateCampaignOperationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('operation', CreateOperationType::class, [
                'label' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'form.operation.fields.create',
                'attr'  => [
                    'class' => 'btn btn-primary trigger-launch',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Campaign::class,
        ]);
    }
}