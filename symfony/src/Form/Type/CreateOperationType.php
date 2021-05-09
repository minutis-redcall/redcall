<?php

namespace App\Form\Type;

use App\Form\Model\Operation;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreateOperationType extends BaseCreateOrUseOperationType
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
            ]);

        $this->prepareChoices($builder);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Operation::class,
        ]);
    }
}