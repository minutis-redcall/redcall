<?php

namespace App\Form\Type;

use App\Form\Model\CampaignOperation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UseOperationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('name', TextType::class, [
                'label'    => 'form.operation.fields.name',
                'required' => false,
            ])
            ->add('ownerExternalId', VolunteerWidgetType::class, [
                'label' => 'form.operation.fields.owner_external_id',

            ])
            ->add('operationExternalId', TextType::class, [

            ])
            ->add('choices', TextType::class, [

            ])
            ->add('submit', SubmitType::class, [
                'label' => 'form.operation.fields.submit',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CampaignOperation::class,
        ]);
    }
}