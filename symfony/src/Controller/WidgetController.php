<?php

namespace App\Controller;

use App\Base\BaseController;
use App\Entity\Badge;
use App\Entity\Campaign;
use App\Entity\Category;
use App\Entity\PrefilledAnswers;
use App\Entity\Structure;
use App\Entity\User;
use App\Entity\Volunteer;
use App\Form\Type\BadgeWidgetType;
use App\Form\Type\CategoryWigetType;
use App\Form\Type\StructureWidgetType;
use App\Form\Type\VolunteerWidgetType;
use App\Manager\BadgeManager;
use App\Manager\CampaignManager;
use App\Manager\CategoryManager;
use App\Manager\PrefilledAnswersManager;
use App\Manager\StructureManager;
use App\Manager\TagManager;
use App\Manager\UserManager;
use App\Manager\VolunteerManager;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
     * @var BadgeManager
     */
    private $badgeManager;

    /**
     * @var CategoryManager
     */
    private $categoryManager;

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var TagManager
     */
    private $tagManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param CampaignManager         $campaignManager
     * @param PrefilledAnswersManager $prefilledAnswersManager
     * @param VolunteerManager        $volunteerManager
     * @param StructureManager        $structureManager
     * @param BadgeManager            $badgeManager
     * @param CategoryManager         $categoryManager
     * @param UserManager             $userManager
     * @param TagManager              $tagManager
     * @param TranslatorInterface     $translator
     */
    public function __construct(CampaignManager $campaignManager,
        PrefilledAnswersManager $prefilledAnswersManager,
        VolunteerManager $volunteerManager,
        StructureManager $structureManager,
        BadgeManager $badgeManager,
        CategoryManager $categoryManager,
        UserManager $userManager,
        TagManager $tagManager,
        TranslatorInterface $translator)
    {
        $this->campaignManager         = $campaignManager;
        $this->prefilledAnswersManager = $prefilledAnswersManager;
        $this->volunteerManager        = $volunteerManager;
        $this->structureManager        = $structureManager;
        $this->badgeManager            = $badgeManager;
        $this->categoryManager         = $categoryManager;
        $this->userManager             = $userManager;
        $this->tagManager              = $tagManager;
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

        $prefilledAnswers = $this->prefilledAnswersManager->findByUserForStructureAndGlobal(
            $this->getUser()
        );

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

    public function nivolEditor(User $user = null)
    {
        $form = $this
            ->createNamedFormBuilder(
                sprintf('nivol-%s', Uuid::uuid4()),
                FormType::class
            )
            ->add('nivol', VolunteerWidgetType::class, [
                'data'  => $user ? $user->getNivol() : null,
                'label' => false,
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

        $criteria = ltrim(trim($request->query->get('keyword')), '0');

        if ($searchAll) {
            $volunteers = $this->volunteerManager->searchAll($criteria, 20);
        } else {
            $volunteers = $this->volunteerManager->searchForCurrentUser($criteria, 20);
        }

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

        $criteria = trim($request->query->get('keyword'));

        if ($searchAll) {
            $structures = $this->structureManager->searchAll($criteria, 20);
        } else {
            $structures = $this->structureManager->searchForCurrentUser($criteria, 20);
        }

        $results = [];
        foreach ($structures as $structure) {
            /** @var Structure $structure */
            $results[] = $structure->toSearchResults();
        }

        return $this->json($results);
    }

    public function badgeEditor()
    {
        $form = $this
            ->createNamedFormBuilder(
                sprintf('badge-%s', Uuid::uuid4()),
                FormType::class
            )
            ->add('badge', BadgeWidgetType::class, ['label' => false])
            ->getForm();

        return $this->render('widget/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route(path="/badge-search", name="badge_search")
     */
    public function badgeSearch(Request $request)
    {
        $criteria = trim($request->query->get('keyword'));

        $badges = $this->badgeManager->searchForCompletion($criteria, 20);

        $results = [];
        foreach ($badges as $badge) {
            /** @var Badge $badge */
            $results[] = $badge->toSearchResults();
        }

        return $this->json($results);
    }

    public function categoryEditor()
    {
        $form = $this
            ->createNamedFormBuilder(
                sprintf('category-%s', Uuid::uuid4()),
                FormType::class
            )
            ->add('category', CategoryWigetType::class, ['label' => false])
            ->getForm();

        return $this->render('widget/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route(path="/category-search", name="category_search")
     */
    public function categorySearch(Request $request)
    {
        $criteria = trim($request->query->get('keyword'));

        $categories = $this->categoryManager->search($criteria, 20);

        $results = [];
        foreach ($categories as $category) {
            /** @var Category $category */
            $results[] = $category->toSearchResults();
        }

        return $this->json($results);
    }

    /**
     * @Route(path="/audience/search", name="audience_search")
     */
    public function audienceSearch(Request $request)
    {
        $structure = $this->getStructure(
            trim($request->get('structureId'))
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
        $tags = array_map(function (int $tag) {
            if (!$entity = $this->tagManager->find($tag)) {
                throw $this->createNotFoundException();
            }

            return $entity;
        }, $request->get('tags', []));

        $structures = [];
        foreach ($request->get('structures') as $structure) {
            $structures[] = $this->getStructure($structure);
        }

        $view = [];
        foreach ($structures as $structure) {
            /** @var Structure $structure */
            $view[$structure->getId()] = $this->volunteerManager->searchVolunteerAudienceByTags($tags, $structure);
        }

        return new JsonResponse($view);
    }

    private function getStructure(int $id) : Structure
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