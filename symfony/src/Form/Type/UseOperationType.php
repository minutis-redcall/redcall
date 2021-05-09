<?php

namespace App\Form\Type;

use App\Form\Model\CampaignOperation;
use App\Security\Helper\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;

class UseOperationType extends AbstractType
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
                'label'       => 'form.operation.fields.structure_use',
                'choices'     => array_flip($this->security->getUser()->getStructuresAsList()),
                'required'    => false,
                'constraints' => [
                    new NotBlank(),
                    new Choice(['choices' => array_flip($this->security->getUser()->getStructuresAsList())]),
                ],
            ])
            ->add('operation', ChoiceType::class, [
                'label'  => 'form.operation.fields.operation_use',
                'mapped' => false,
            ])
            ->add('operationExternalId', TextType::class, [
                'label' => 'form.operation.fields.operation_id',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CampaignOperation::class,
        ]);
    }
}