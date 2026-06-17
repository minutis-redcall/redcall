<?php

namespace App\Controller\Admin;

use App\Base\BaseController;
use App\Manager\DeletedVolunteerManager;
use App\Manager\VolunteerAuditLogManager;
use Bundles\PaginationBundle\Manager\PaginationManager;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route("/admin/gdpr", name: "admin_gdpr_")]
class GdprController extends BaseController
{
    /**
     * @var DeletedVolunteerManager
     */
    private $deletedVolunteerManager;

    /**
     * @var VolunteerAuditLogManager
     */
    private $volunteerAuditLogManager;

    /**
     * @var PaginationManager
     */
    private $paginationManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(DeletedVolunteerManager $deletedVolunteerManager,
        VolunteerAuditLogManager $volunteerAuditLogManager,
        PaginationManager $paginationManager,
        TranslatorInterface $translator)
    {
        $this->deletedVolunteerManager  = $deletedVolunteerManager;
        $this->volunteerAuditLogManager = $volunteerAuditLogManager;
        $this->paginationManager        = $paginationManager;
        $this->translator               = $translator;
    }

    #[Route(name: "index")]
    #[Template("admin/gdpr/index.html.twig")]
    public function index(Request $request) : array
    {
        $form = $this
            ->createFormBuilder()
            ->add('external_id', TextType::class, [
                'label'       => 'admin.gdpr.form.external_id',
                'constraints' => [
                    new NotBlank(),
                    new Callback(function ($data, ExecutionContextInterface $context) {
                        if (!$this->deletedVolunteerManager->isDeleted($data)) {
                            $context->addViolation(
                                $this->translator->trans('admin.gdpr.violations.not_exists')
                            );
                        }
                    }),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'admin.gdpr.form.submit',
            ])
            ->getForm()
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->deletedVolunteerManager->undelete(
                $form->get('external_id')->getData()
            );

            $this->addFlash('success', 'admin.gdpr.success');
        }

        return [
            'search' => $form->createView(),
        ];
    }

    #[Route("/history", name: "history")]
    #[Template("admin/gdpr/history.html.twig")]
    public function history(Request $request) : array
    {
        $search   = $this->createHistorySearchForm($request);
        $criteria = null;

        // Sync-driven anonymizes are the loudest signal of "did production
        // eat my volunteer?" — show them by default, give admins a checkbox
        // to drop them and focus on human-driven anonymizes only.
        $hideTechnical = false;

        if ($search->isSubmitted() && $search->isValid()) {
            $criteria      = $search->get('criteria')->getData();
            $hideTechnical = (bool) $search->get('hideTechnical')->getData();
        }

        // Forward the form's GET payload into every page link — otherwise
        // jumping to page 2 drops the criteria and the checkbox state.
        $queryParams = $request->query->all();
        unset($queryParams['page']);

        return [
            'search'      => $search->createView(),
            'criteria'    => $criteria,
            'queryParams' => $queryParams,
            'entries'     => $this->paginationManager->getPager(
                $this->volunteerAuditLogManager->searchQueryBuilder($criteria, $hideTechnical),
                '',
                true
            ),
        ];
    }

    private function createHistorySearchForm(Request $request)
    {
        return $this
            ->createFormBuilder(null, ['csrf_protection' => false])
            ->setMethod('GET')
            ->add('criteria', TextType::class, [
                'label'    => 'admin.gdpr.history.search.criteria',
                'required' => false,
            ])
            ->add('hideTechnical', CheckboxType::class, [
                'label'    => 'admin.gdpr.history.search.hide_technical',
                'required' => false,
                'data'     => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'admin.gdpr.history.search.submit',
            ])
            ->getForm()
            ->handleRequest($request);
    }
}