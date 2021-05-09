<?php

namespace App\Form\Flow;

use App\Form\Model\Campaign;
use App\Form\Type\CampaignType;
use App\Form\Type\CreateCampaignOperationType;
use App\Form\Type\CreateOrUseOperationType;
use App\Form\Type\UseCampaignOperationType;
use Craue\FormFlowBundle\Form\FormFlow;
use Craue\FormFlowBundle\Form\FormFlowInterface;

class CampaignFlow extends FormFlow
{
    protected function loadStepsConfig()
    {
        return [
            1 => [
                'form_type' => CampaignType::class,
            ],
            2 => [
                'form_type' => CreateOrUseOperationType::class,
                'skip'      => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
                    /** @var Campaign $data */
                    $data = $flow->getFormData();

                    return !$data->hasOperation;
                },
            ],
            3 => [
                'form_type' => CreateCampaignOperationType::class,
                'skip'      => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
                    /** @var Campaign $data */
                    $data = $flow->getFormData();

                    return !$data->hasOperation || !$data->createOperation;
                },
            ],
            4 => [
                'form_type' => UseCampaignOperationType::class,
                'skip'      => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
                    /** @var Campaign $data */
                    $data = $flow->getFormData();

                    return !$data->hasOperation || $data->createOperation;
                },
            ],
        ];
    }
}