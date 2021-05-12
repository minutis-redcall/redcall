<?php

namespace App\Form\Type;

use App\Form\Model\Campaign;
use App\Form\Model\Operation;
use App\Manager\OperationManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class UseCampaignOperationType extends AbstractType
{
    /**
     * @var OperationManager
     */
    private $operationManager;

    public function __construct(OperationManager $operationManager)
    {
        $this->operationManager = $operationManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('operation', UseOperationType::class, [
                'label'       => false,
                'constraints' => [
                    new Callback(function ($object, ExecutionContextInterface $context, $payload) {
                        /** @var Operation $object */
                        if (!$this->operationManager->isOperationExisting($object->operationExternalId)) {
                            $context
                                ->buildViolation('form.operation.does_not_exist')
                                ->atPath('operationExternalId')
                                ->addViolation();
                        }
                    }),
                ],
            ])
            ->add('submit', SubmitType::class, [
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