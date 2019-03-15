<?php

namespace App\Controller;

use App\Base\BaseController;
use App\Entity\Campaign;
use App\Entity\PrefilledAnswers;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class PrefilledAnswersController extends BaseController
{
    public function renderWidget(?int $campaignId = null)
    {
        $currentColor = Campaign::TYPE_GREEN;

        if ($campaignId) {
            $campaign = $this->getManager(Campaign::class)->find($campaignId);
            if (!$campaign) {
                throw $this->createNotFoundException();
            }

            $currentColor = $campaign->getType();
        }

        $prefilledAnswers = $this->getManager(PrefilledAnswers::class)->findAll();

        $choices = [];
        foreach ($prefilledAnswers as $prefilledAnswer) {
            foreach (Campaign::TYPES as $color) {
                if (in_array($color, $prefilledAnswer->getColors())) {
                    $choices[$color][$prefilledAnswer->getLabel()] = $prefilledAnswer->getId();
                }
            }
        }

        $answers = [];
        foreach ($prefilledAnswers as $prefilledAnswer) {
            $answers[$prefilledAnswer->getId()] = $prefilledAnswer->getAnswers();
        }

        $forms = [];
        foreach (Campaign::TYPES as $color) {
            $forms[$color] = $this->createNamedFormBuilder($color, ChoiceType::class, null, [
                'label'    => false,
                'required' => false,
                'choices'  => $choices[$color] ?? [],
                'attr'     => [
                    'class' => 'prefilled-answers-selector',
                ],
            ])->getForm()->createView();
        }

        return $this->render('prefilled_answers/widget.html.twig', [
            'current_color' => $currentColor,
            'forms'         => $forms,
            'answers'       => $answers,
        ]);
    }
}