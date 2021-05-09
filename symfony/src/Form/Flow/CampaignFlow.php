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
                'form_type'    => CampaignType::class,
                'form_options' => [
                    'validation_groups' => ['Default'],
                ],
            ],
            2 => [
                'form_type'    => CreateOrUseOperationType::class,
                'form_options' => [
                    'validation_groups' => ['Default'],
                ],
                'skip'         => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
                    /** @var Campaign $data */
                    $data = $flow->getFormData();

                    return !$data->hasOperation;
                },
            ],
            3 => [
                'form_type'    => CreateCampaignOperationType::class,
                'form_options' => [
                    'validation_groups' => ['Create'],
                ],
                'skip'         => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
                    /** @var Campaign $data */
                    $data = $flow->getFormData();

                    return !$data->hasOperation || Campaign::CREATE_OPERATION !== $data->createOperation;
                },
            ],
            4 => [
                'form_type'    => UseCampaignOperationType::class,
                'form_options' => [
                    'validation_groups' => ['Use'],
                ],
                'skip'         => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
                    /** @var Campaign $data */
                    $data = $flow->getFormData();

                    return !$data->hasOperation || Campaign::USE_OPERATION !== $data->createOperation;
                },
            ],
        ];
    }
}