<?php

namespace App\Controller\Admin;

use App\Base\BaseController;
use App\Component\HttpFoundation\NoContentResponse;
use App\Entity\Badge;
use App\Form\Type\BadgeWidgetType;
use App\Form\Type\CategoryWigetType;
use App\Manager\BadgeManager;
use App\Model\Csrf;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/badges", name="admin_badge_")
 */
class BadgeController extends BaseController
{
    /**
     * @var BadgeManager
     */
    private $badgeManager;

    /**
     * @param BadgeManager $badgeManager
     */
    public function __construct(BadgeManager $badgeManager)
    {
        $this->badgeManager = $badgeManager;
    }

    /**
     * @Route(name="index")
     * @Template("admin/badge/badges.html.twig")
     */
    public function index(Request $request) : array
    {
        $searchForm = $this->createSearchForm($request, 'admin.badge.search');

        $badges = $this->getPager(
            $this->badgeManager->getSearchInPublicBadgesQueryBuilder(
                $searchForm->get('criteria')->getData()
            )
        );

        return [
            'badges' => $badges,
            'counts' => $this->badgeManager->getVolunteerCountInSearch($badges),
            'search' => $searchForm->createView(),
        ];
    }

    /**
     * @Route(path="/remove-{id}/{token}", name="remove")
     */
    public function remove(Badge $badge, Csrf $token)
    {
        if ($badge->canBeRemoved()) {
            $this->badgeManager->remove($badge);
        }

        return new NoContentResponse();
    }

    /**
     * @Route(path="/manage-{id}", name="manage")
     * @Template("admin/badge/manage.html.twig")
     */
    public function manage(Request $request, ?Badge $badge = null)
    {
        $form = $this->createManageForm($request, $badge);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($badge->getSynonyms() as $synonym) {
                $synonym->setSynonym($badge);
                $this->badgeManager->save($synonym);
            }

            $this->badgeManager->save($badge);

            return $this->redirectToRoute('admin_badge_index', $request->query->all());
        }

        return [
            'badge' => $badge,
            'form'  => $form->createView(),
        ];
    }

    private function createSearchForm(Request $request, string $label) : FormInterface
    {
        return $this->createFormBuilder(null, ['csrf_protection' => false])
                    ->setMethod('GET')
                    ->add('criteria', TextType::class, [
                        'label'    => $label,
                        'required' => false,
                    ])
                    ->add('submit', SubmitType::class, [
                        'label' => 'base.button.search',
                    ])
                    ->getForm()
                    ->handleRequest($request);
    }

    private function createManageForm(Request $request, ?Badge $badge) : FormInterface
    {
        $builder = $this
            ->createFormBuilder($badge, [
                'data_class' => Badge::class,
            ])
            ->add('name', TextType::class, [
                'label' => 'admin.badge.form.name',
            ])
            ->add('description', TextType::class, [
                'label' => 'admin.badge.form.description',
            ])
            ->add('category', CategoryWigetType::class, [
                'label'    => 'admin.badge.form.category',
                'required' => false,
            ])
            ->add('priority', NumberType::class, [
                'label'    => 'admin.badge.form.priority',
                'required' => false,
            ])
            ->add('parent', BadgeWidgetType::class, [
                'label'    => 'admin.badge.form.parent',
                'required' => false,
            ])
            ->add('synonyms', CollectionType::class, [
                'label'         => 'admin.badge.form.synonyms',
                'entry_type'    => BadgeWidgetType::class,
                'entry_options' => [
                    'label'    => false,
                    'required' => false,
                ],
                'allow_add'     => true,
                'allow_delete'  => true,
                'delete_empty'  => true,
                'prototype'     => true,
                'required'      => false,
                'by_reference'  => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'base.button.save',
            ]);

        return $builder
            ->getForm()
            ->handleRequest($request);
    }

}