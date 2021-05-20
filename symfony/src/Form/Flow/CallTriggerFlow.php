<?php

namespace App\Form\Flow;

use App\Entity\Communication;
use App\Form\Model\BaseTrigger;
use App\Form\Model\CallTrigger;
use App\Form\Model\Campaign;
use App\Form\Model\SmsTrigger;
use App\Form\Type\CampaignType;
use App\Form\Type\ChooseCampaignOperationChoicesType;
use App\Form\Type\ChooseOperationChoicesType;
use App\Form\Type\CreateCampaignOperationType;
use App\Form\Type\CreateOrUseOperationType;
use App\Form\Type\SmsTriggerType;
use App\Form\Type\UseCampaignOperationType;
use Craue\FormFlowBundle\Form\FormFlow;
use Craue\FormFlowBundle\Form\FormFlowInterface;

class CallTriggerFlow extends FormFlow
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
                    /** @var CallTrigger $data */
                    $data = $flow->getFormData();

                    return !$data->isOperation() || 0 === count($data->getAnswers());
                },
            ],
        ];
    }
}