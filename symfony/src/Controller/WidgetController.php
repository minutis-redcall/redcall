<?php

namespace App\Controller;

use App\Base\BaseController;
use App\Entity\Campaign;
use App\Entity\PrefilledAnswers;
use App\Entity\Structure;
use App\Entity\UserInformation;
use App\Entity\Volunteer;
use App\Form\Type\AudienceType;
use App\Form\Type\StructureWidgetType;
use App\Form\Type\VolunteerWidgetType;
use App\Manager\CampaignManager;
use App\Manager\PrefilledAnswersManager;
use App\Manager\StructureManager;
use App\Manager\TagManager;
use App\Manager\UserInformationManager;
use App\Manager\VolunteerManager;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

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
    private $userInformationManager;

    /**
     * @var TagManager
     */
    private $tagManager;

    /**
     * @param CampaignManager         $campaignManager
     * @param PrefilledAnswersManager $prefilledAnswersManager
     * @param VolunteerManager        $volunteerManager
     * @param StructureManager        $structureManager
     * @param TranslatorInterface     $translator
     * @param UserInformationManager  $userInformationManager
     * @param TagManager              $tagManager
     */
    public function __construct(CampaignManager $campaignManager, PrefilledAnswersManager $prefilledAnswersManager, VolunteerManager $volunteerManager, StructureManager $structureManager, TranslatorInterface $translator, UserInformationManager $userInformationManager, TagManager $tagManager)
    {
        $this->campaignManager = $campaignManager;
        $this->prefilledAnswersManager = $prefilledAnswersManager;
        $this->volunteerManager = $volunteerManager;
        $this->structureManager = $structureManager;
        $this->translator = $translator;
        $this->userInformationManager = $userInformationManager;
        $this->tagManager = $tagManager;
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

        $userInformation = $this->userInformationManager->findForCurrentUser();
        $prefilledAnswers = $this->prefilledAnswersManager->findByUserForStructureAndGlobal($userInformation);

        $choices = [];
        /** @var PrefilledAnswers $prefilledAnswer */
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
            $results[] = $structure->toSearchResults();
        }

        return $this->json($results);
    }

    /**
     * @Route(path="/audience/search", name="audience_search")
     */
    public function audienceSearch(Request $request)
    {
        $structure = $this->getStructure(
            $request->get('structureId')
        );

        $load = $request->get('load');
        if ($load) {
            if (!is_array($request->get('load'))) {
                throw $this->createNotFoundException();
            }

            $results = $this->volunteerManager->loadVolunteersAudience($structure, $request->get('load'));
        } else {
            if (!$request->get('keyword')) {
                throw $this->createNotFoundException();
            }

            $results = $this->volunteerManager->searchVolunteersAudience($structure, $request->get('keyword'));
        }

        return new JsonResponse([
            'results' => $results,
            'options' => [],
        ]);
    }

    /**
     * @Route(path="/audience/toggle-tag", name="audience_toggle_tag")
     */
    public function audienceToggleTag(Request $request)
    {
        $tag = $this->tagManager->find(
            $request->get('tag')
        );

        if (!$tag) {
            throw $this->createNotFoundException();
        }

        $structures = [];
        foreach ($request->get('structures') as $structure) {
            $structures[] = $this->getStructure($structure);
        }

        $view = [];
        foreach ($structures as $structure) {
            /** @var Structure $structure */
            $view[$structure->getId()] = $this->volunteerManager->searchVolunteerAudienceByTag($tag, $structure);
        }

        return new JsonResponse($view);
    }

    /**
     * @Route(path="/audience/classify", name="audience_classify")
     */
    public function audienceClassify(Request $request)
    {
        $form = $this->createFormBuilder()
            ->add('audience', AudienceType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $classification = $this->volunteerManager->classifyNivols(
                $form->get('audience')->getData()
            );

            return $this->render('widget/classification.html.twig', [
                'classified' => $classification,
            ]);
        }

        return new Response();
    }

    private function getStructure(int $id): Structure
    {
        $structure = $this->structureManager->find($id);

        if (!$structure) {
            throw $this->createNotFoundException();
        }

        if (!$this->isGranted('STRUCTURE', $structure)) {
            throw $this->createAccessDeniedException();
        }

        return $structure;
    }
}