<?php

namespace App\Form\Type;

use App\Form\Model\CampaignOperation;
use App\Security\Helper\Security;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;

class CreateOperationType extends BaseCreateOrUseOperationType
{
    /**
     * @var Security
     */
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('structure', ChoiceType::class, [
                'label'       => 'form.operation.fields.structure_create',
                'choices'     => array_flip($this->security->getUser()->getStructuresAsList()),
                'constraints' => [
                    new Choice(['choices' => array_flip($this->security->getUser()->getStructuresAsList())]),
                ],
            ])
            ->add('name', TextType::class, [
                'label'    => 'form.operation.fields.name',
                'required' => false,
            ])
            ->add('ownerExternalId', VolunteerWidgetType::class, [
                'label' => 'form.operation.fields.owner_external_id',
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var CampaignOperation $data */
            $data                  = $event->getData();
            $data->name            = $data->campaign->label;
            $data->ownerExternalId = $this->security->getUser()->getExternalId();
        });

        $this->prepareChoices($builder);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CampaignOperation::class,
        ]);
    }
}