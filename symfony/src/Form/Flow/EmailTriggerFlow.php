<?php

namespace App\Form\Flow;

use App\Form\Model\EmailTrigger;
use App\Form\Type\ChooseOperationChoicesType;
use App\Form\Type\EmailTriggerType;
use Craue\FormFlowBundle\Form\FormFlow;
use Craue\FormFlowBundle\Form\FormFlowInterface;

class EmailTriggerFlow extends FormFlow
{
    protected function loadStepsConfig()
    {
        return [
            1 => [
                'form_type'    => EmailTriggerType::class,
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
                    /** @var EmailTrigger $data */
                    $data = $flow->getFormData();

                    return !$data->isOperation() || 0 === count($data->getAnswers());
                },
            ],
        ];
    }
}