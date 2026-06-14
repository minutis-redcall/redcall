<?php

namespace App\Controller\Admin;

use App\Base\BaseController;
use App\Manager\UserAuditLogManager;
use Bundles\PaginationBundle\Manager\PaginationManager;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route("/admin/redcall-users/history", name: "admin_redcall_users_")]
class UserAuditLogController extends BaseController
{
    /**
     * @var UserAuditLogManager
     */
    private $userAuditLogManager;

    /**
     * @var PaginationManager
     */
    private $paginationManager;

    public function __construct(UserAuditLogManager $userAuditLogManager,
        PaginationManager $paginationManager)
    {
        $this->userAuditLogManager = $userAuditLogManager;
        $this->paginationManager   = $paginationManager;
    }

    #[Route("", name: "history")]
    public function index(Request $request)
    {
        $search   = $this->createSearchForm($request);
        $criteria = null;

        // automation rows (actor = null) are noise for most lookups, hide them
        // unless the "hide technical logs" checkbox gets explicitly unchecked
        $hideTechnical = true;

        if ($search->isSubmitted() && $search->isValid()) {
            $criteria      = $search->get('criteria')->getData();
            $hideTechnical = (bool) $search->get('hideTechnical')->getData();
        }

        return $this->render('admin/users/audit_log/index.html.twig', [
            'search'   => $search->createView(),
            'criteria' => $criteria,
            'entries'  => $this->paginationManager->getPager(
                $this->userAuditLogManager->searchQueryBuilder($criteria, $hideTechnical),
                '',
                true
            ),
        ]);
    }

    private function createSearchForm(Request $request)
    {
        return $this
            ->createFormBuilder(null, ['csrf_protection' => false])
            ->setMethod('GET')
            ->add('criteria', TextType::class, [
                'label'    => 'admin.users.history.search.criteria',
                'required' => false,
            ])
            ->add('hideTechnical', CheckboxType::class, [
                'label'    => 'admin.users.history.search.hide_technical',
                'required' => false,
                'data'     => true,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'admin.users.history.search.submit',
            ])
            ->getForm()
            ->handleRequest($request);
    }
}
