<?php

namespace App\Controller;

use App\Base\BaseController;
use App\Entity\Campaign;
use App\Entity\UserInformation;
use App\Form\Type\NivolType;
use App\Manager\CampaignManager;
use App\Manager\PrefilledAnswersManager;
use App\Manager\VolunteerManager;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\VarDumper\VarDumper;

/**
 * @Route(path="/widget", name="widget_")
 */
class WidgetController extends BaseController
{
    /**
     * @var CampaignManager
     */
    private $campaignManager;

    /**
     * @var PrefilledAnswersManager
     */
    private $prefilledAnswersManager;

    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param CampaignManager         $campaignManager
     * @param PrefilledAnswersManager $prefilledAnswersManager
     * @param VolunteerManager        $volunteerManager
     * @param TranslatorInterface     $translator
     */
    public function __construct(CampaignManager $campaignManager,
        PrefilledAnswersManager $prefilledAnswersManager,
        VolunteerManager $volunteerManager,
        TranslatorInterface $translator)
    {
        $this->campaignManager         = $campaignManager;
        $this->prefilledAnswersManager = $prefilledAnswersManager;
        $this->volunteerManager        = $volunteerManager;
        $this->translator              = $translator;
    }

    public function prefilledAnswers(?int $campaignId = null)
    {
        $currentColor = Campaign::TYPE_GREEN;

        if ($campaignId) {
            $campaign = $this->campaignManager->find($campaignId);
            if (!$campaign) {
                throw $this->createNotFoundException();
            }

            $currentColor = $campaign->getType();
        }

        $prefilledAnswers = $this->prefilledAnswersManager->findAll();

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

        return $this->render('widget/prefilled_answers_dropdown.html.twig', [
            'current_color' => $currentColor,
            'forms'         => $forms,
            'answers'       => $answers,
        ]);
    }

    public function nivolEditor(UserInformation $userInformation)
    {
        VarDumper::dump($userInformation);
        $form = $this
            ->createNamedFormBuilder(
                sprintf('nivol-%s', Uuid::uuid4()),
                FormType::class
            )
            ->add('nivol', NivolType::class, [
                'data' => $userInformation->getNivol(),
            ])
            ->getForm();

        return $this->render('widget/nivol_editor.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route(path="/nivol-search/{searchAll}", name="nivol_search")
     */
    public function nivolSearch(Request $request, bool $searchAll = false)
    {
        if ($searchAll && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $volunteers = $this->volunteerManager->search(
            $request->query->get('keyword'),
            20,
            $searchAll ? null : $this->getUser()
        );

        // Format volunteer for the flexdatalist rendering
        $results = [];
        foreach ($volunteers as $volunteer) {
            /* @var \App\Entity\Volunteer $volunteer */
            $results[] = $volunteer->toSearchResults($this->translator);
        }

        return $this->json($results);
    }
}