<?php

namespace App\Form\Flow;

use App\Form\Model\SmsTrigger;
use App\Form\Type\ChooseOperationChoicesType;
use App\Form\Type\SmsTriggerType;
use Craue\FormFlowBundle\Form\FormFlow;
use Craue\FormFlowBundle\Form\FormFlowInterface;

class SmsTriggerFlow extends FormFlow
{
    protected function loadStepsConfig()
    {
        return [
            1 => [
                'form_type'    => SmsTriggerType::class,
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
                    /** @var SmsTrigger $data */
                    $data = $flow->getFormData();

                    return !$data->isOperation() || 0 === count($data->getAnswers());
                },
            ],
        ];
    }
}