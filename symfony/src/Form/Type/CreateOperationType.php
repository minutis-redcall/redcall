<?php

namespace App\Form\Type;

use App\Form\Model\Operation;
use App\Security\Helper\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;

class CreateOperationType extends AbstractType
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
        $builder
            ->add('structureExternalId', ChoiceType::class, [
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
            /** @var Operation $data */
            $data = $event->getData();

            $name                  = sprintf('%s: %s', date('d/m/Y'), $data->campaign->label);
            $data->name            = $name;
            $data->ownerExternalId = $this->security->getUser()->getExternalId();
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Operation::class,
        ]);
    }
}