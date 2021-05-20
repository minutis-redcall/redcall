<?php

namespace App\Form\Flow;

use App\Form\Model\CallTrigger;
use App\Form\Type\CallTriggerType;
use App\Form\Type\ChooseOperationChoicesType;
use Craue\FormFlowBundle\Form\FormFlow;
use Craue\FormFlowBundle\Form\FormFlowInterface;

class CallTriggerFlow extends FormFlow
{
    protected function loadStepsConfig()
    {
        return [
            1 => [
                'form_type'    => CallTriggerType::class,
                'form_options' => [
                    'validation_groups' => ['Default'],
                ],
            ],
            2 => [
                'form_type'    => ChooseOperationChoicesType::class,
                'form_options' => [
                    'validation_groups' => ['Default'],
                ],
                'skip'         => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
                    /** @var CallTrigger $data */
                    $data = $flow->getFormData();

                    return !$data->isOperation() || 0 === count($data->getAnswers());
                },
            ],
        ];
    }
}