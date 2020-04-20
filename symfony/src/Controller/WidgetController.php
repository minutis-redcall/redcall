<?php

namespace App\Controller;

use App\Base\BaseController;
use App\Entity\Campaign;
use App\Entity\Structure;
use App\Entity\UserInformation;
use App\Entity\Volunteer;
use App\Form\Type\StructureWidgetType;
use App\Form\Type\VolunteerWidgetType;
use App\Manager\CampaignManager;
use App\Manager\PrefilledAnswersManager;
use App\Manager\StructureManager;
use App\Manager\UserInformationManager;
use App\Manager\VolunteerManager;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

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
     * @var StructureManager
     */
    private $structureManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var UserInformationManager
     */
    private $informationManager;

    /**
     * @param CampaignManager         $campaignManager
     * @param PrefilledAnswersManager $prefilledAnswersManager
     * @param VolunteerManager        $volunteerManager
     * @param StructureManager        $structureManager
     * @param TranslatorInterface     $translator
     */
    public function __construct(CampaignManager $campaignManager,
        PrefilledAnswersManager $prefilledAnswersManager,
        VolunteerManager $volunteerManager,
        StructureManager $structureManager,
        TranslatorInterface $translator,
        UserInformationManager $informationManager)
    {
        $this->campaignManager         = $campaignManager;
        $this->prefilledAnswersManager = $prefilledAnswersManager;
        $this->volunteerManager        = $volunteerManager;
        $this->structureManager        = $structureManager;
        $this->translator              = $translator;
        $this->informationManager      = $informationManager;
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

        $userInformation = $this->informationManager->findForCurrentUser();
        $prefilledAnswers = $this->prefilledAnswersManager->findByUserForStructureAndGlobal($userInformation);

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

    public function nivolEditor(UserInformation $userInformation = null)
    {
        $form = $this
            ->createNamedFormBuilder(
                sprintf('nivol-%s', Uuid::uuid4()),
                FormType::class
            )
            ->add('nivol', VolunteerWidgetType::class, [
                'data' => $userInformation ? $userInformation->getNivol() : null,
            ])
            ->getForm();

        return $this->render('widget/form.html.twig', [
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

        $criteria = $request->query->get('keyword');

        if ($searchAll) {
            $volunteers = $this->volunteerManager->searchAll($criteria, 20);
        } else {
            $volunteers = $this->volunteerManager->searchForCurrentUser($criteria, 20);
        }

        // Format volunteer for the flexdatalist rendering
        $results = [];
        foreach ($volunteers as $volunteer) {
            /* @var Volunteer $volunteer */
            $results[] = $volunteer->toSearchResults($this->translator);
        }

        return $this->json($results);
    }

    public function structureEditor()
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $form = $this
            ->createNamedFormBuilder(
                sprintf('structure-%s', Uuid::uuid4()),
                FormType::class
            )
            ->add('structure', StructureWidgetType::class, ['label' => false])
            ->getForm();

        return $this->render('widget/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route(path="/structure-search/{searchAll}", name="structure_search")
     */
    public function structureSearch(Request $request, bool $searchAll = false)
    {
        if ($searchAll && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $criteria = $request->query->get('keyword');

        if ($searchAll) {
            $structures = $this->structureManager->searchAll($criteria, 20);
        } else {
            $structures = $this->structureManager->searchForCurrentUser($criteria, 20);
        }

        // Format volunteer for the flexdatalist rendering
        $results = [];
        foreach ($structures as $structure) {
            /** @var Structure $structure */
            $results[] = $structure->toSearchResults($this->translator);
        }

        return $this->json($results);
    }

    /**
     * @Route(path="/nivols", name="nivols")
     */
    public function nivols(Request $request)
    {
        $nivols = array_unique(array_filter(preg_split('/[^0-9a-z*]/ui', $request->get('nivols'))));

        $view = $this->renderView('widget/nivols.html.twig', [
            'classified' => $this->volunteerManager->classifyNivols($nivols),
        ]);

        return new JsonResponse([
            'success' => true,
            'view' => $view,
        ]);
    }
}