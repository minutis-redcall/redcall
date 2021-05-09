<?php

namespace App\Form\Type;

use App\Form\Model\Campaign;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreateOrUseOperationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('createOperation', ChoiceType::class, [
                'label'    => false,
                'expanded' => true,
                'choices'  => [
                    'form.operation.fields.choices.create' => true,
                    'form.operation.fields.choices.use'    => false,
                ],
            ])
            ->add('continue', SubmitType::class, [
                'label' => 'form.operation.buttons.continue',
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