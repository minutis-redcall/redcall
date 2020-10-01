<?php

namespace App\Form\Type;

use App\Enum\Type;
use App\Form\Model\Campaign as CampaignModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CampaignType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('label', TextType::class, [
                'label'    => 'form.campaign.fields.label',
                'required' => false,
            ])
            ->add('type', TypesType::class)
            ->add('trigger', $options['type']->getFormType(), [
                'label' => false,
            ])
            ->add('notes', TextareaType::class, [
                'label'    => 'form.campaign.fields.notes',
                'required' => false,
            ])
        ;

        $builder->get('notes')->addModelTransformer(new CallbackTransformer(
            function (?string $fromData) {
                return $fromData;
            },
            function (?string $fromForm) {
                return $fromForm ? strip_tags($fromForm) : null;
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CampaignModel::class,
            'type' => Type::SMS(),
        ]);
    }
}
