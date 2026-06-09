<?php

namespace App\Controller\Admin;

use App\Base\BaseController;
use App\Entity\Structure;
use App\Entity\User;
use App\Form\Type\ManageUserStructuresType;
use App\Form\Type\VolunteerWidgetType;
use App\Manager\BadgeManager;
use App\Manager\StructureManager;
use App\Manager\UserAuditLogManager;
use App\Manager\UserManager;
use App\Manager\VolunteerManager;
use Bundles\PaginationBundle\Manager\PaginationManager;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 *
 * Administration tools for the RedCall users tied to volunteer NIVOLs:
 * privilege toggles, structure assignment, RTMR list, etc. The PasswordLogin
 * bundle owns the generic /admin/users/ list — this controller lives under
 * /admin/redcall-users to avoid the collision.
 */
#[Route("/admin/redcall-users", name: "admin_redcall_users_")]
class UserController extends BaseController
{
    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var StructureManager
     */
    private $structureManager;

    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @var PaginationManager
     */
    private $paginationManager;

    /**
     * @var BadgeManager
     */
    private $badgeManager;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var UserAuditLogManager
     */
    private $userAuditLogManager;

    public function __construct(UserManager $userManager,
        StructureManager $structureManager,
        VolunteerManager $volunteerManager,
        PaginationManager $paginationManager,
        BadgeManager $badgeManager,
        RequestStack $requestStack,
        UserAuditLogManager $userAuditLogManager)
    {
        $this->userManager         = $userManager;
        $this->structureManager    = $structureManager;
        $this->volunteerManager    = $volunteerManager;
        $this->paginationManager   = $paginationManager;
        $this->badgeManager        = $badgeManager;
        $this->requestStack        = $requestStack;
        $this->userAuditLogManager = $userAuditLogManager;
    }

    #[Route(name: "index")]
    public function index()
    {
        $request = $this->requestStack->getMainRequest();
        $search  = $this->createSearchForm($request);

        if ($search->isSubmitted() && $search->isValid()) {
            $criteria = $search->get('criteria')->getData();
        }

        return $this->render('admin/users/index.html.twig', [
            'search'    => $search->createView(),
            'type'      => ($request->attributes->get('type') ?? $request->query->get('type') ?? $request->request->get('type')),
            'users'     => $this->paginationManager->getPager(
                $this->userManager->searchQueryBuilder($criteria ?? null, false)
            ),
        ]);
    }

    #[Route(name: "list_users", path: "/list-users")]
    public function userList()
    {
        $users = $this->userManager->findAll();

        $list = array_filter(array_map(function (User $user) {
            return $user->getExternalId();
        }, $users));

        return $this->render('admin/users/user_list.html.twig', [
            'list' => $list,
        ]);
    }

    #[Route(name: "update", path: "/update/{csrf}/{id}")]
#[IsGranted("USER", subject: "user")]
    public function updateBoundVolunteer(Request $request, string $csrf, User $user)
    {
        $this->validateCsrfOrThrowNotFoundException('pegass', $csrf);

        $externalId = $request->request->get('externalId');

        if (!$user->isLocked()) {
            $old = $this->userAuditLogManager->buildSnapshot($user);
            $this->userManager->changeVolunteer($user, $externalId);
            $this->userAuditLogManager->logUpdated($this->resolveActor(), null, $user, $old);
        }

        $structureNames = array_filter(array_map(function (Structure $structure) {
            return $structure->getName();
        }, $user->getStructures()->toArray()));

        return $this->json([
            'structures' => array_map('htmlentities', $structureNames),
        ]);
    }

    #[Route(name: "update_structures", path: "/update-structures/{id}")]
#[IsGranted("USER", subject: "user")]
    public function updateStructures(Request $request, User $user)
    {
        // It's a reverse use of the entity type: we want to show all
        // user structures unticked, and tick only the ones we wish
        // to delete. So when submitting, we'll delete from user
        // entity the ones that exist on the cloned entity.
        $clone = clone $user;
        foreach ($clone->getStructures(false) as $structure) {
            $clone->removeStructure($structure);
        }

        $form = $this
            ->createForm(ManageUserStructuresType::class, $clone, [
                'user' => $user,
            ])
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $old = $this->userAuditLogManager->buildSnapshot($user);

            foreach ($clone->getStructures(false) as $structure) {
                $user->removeStructure($structure);
            }

            // Freeze user to keep prevent Pegass from overwriting the change
            $user->setLocked(true);
            $this->userManager->save($user);

            $this->userAuditLogManager->logUpdated($this->resolveActor(), null, $user, $old);

            return $this->redirectToRoute('admin_redcall_users_update_structures', [
                'id' => $user->getId(),
            ]);
        }

        return $this->render('admin/users/structures.html.twig', [
            'user'       => $user,
            'form'       => $form->createView(),
            'structures' => $this->structureManager->getStructuresForUser($user),
        ]);
    }

    #[Route(name: "add_structure", path: "/add-structure/{csrf}/{id}")]
#[IsGranted("USER", subject: "user")]
    public function addStructure(Request $request, string $csrf, User $user)
    {
        $this->validateCsrfOrThrowNotFoundException('pegass', $csrf);

        $structureId = ($request->attributes->get('structure') ?? $request->query->get('structure') ?? $request->request->get('structure'));
        if (!$structureId) {
            throw $this->createNotFoundException();
        }

        $parentStructure = $this->structureManager->find($structureId);
        if (!$parentStructure) {
            throw $this->createNotFoundException();
        }

        $old = $this->userAuditLogManager->buildSnapshot($user);

        $structures = $this->structureManager->findCallableStructuresForStructure($parentStructure);
        foreach ($structures as $structure) {
            $user->addStructure($structure);
        }

        // Freeze user to keep prevent Pegass from overwriting the change
        $user->setLocked(true);

        $this->userManager->save($user);

        $this->userAuditLogManager->logUpdated($this->resolveActor(), null, $user, $old);

        return $this->redirectToRoute('admin_redcall_users_update_structures', [
            'id' => $user->getId(),
        ]);
    }

    #[Route(name: "create_user", path: "/create-user")]
    public function createUser(Request $request, KernelInterface $kernel)
    {
        $form = $this->createFormBuilder()
                     ->add('externalId', VolunteerWidgetType::class, [
                         'label' => false,
                     ])
                     ->add('submit', SubmitType::class, [
                         'label' => 'base.button.create',
                     ])
                     ->getForm()
                     ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $volunteer = $this->volunteerManager->findOneByExternalId(
                $form->get('externalId')->getData()
            );

            if (!$volunteer) {
                throw $this->createNotFoundException();
            }

            $actor = $this->resolveActor();
            $this->userManager->createUser($volunteer->getExternalId(), $actor ? $actor->getId() : null);

            return $this->redirectToRoute('admin_redcall_users_index', [
                'form[criteria]' => $volunteer->getExternalId(),
            ]);
        }

        return $this->render('admin/users/create_user.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(name: "toggle_verify", path: "/toggle-verify/{csrf}/{id}")]
#[IsGranted("USER", subject: "user")]
    public function toggleVerifyAction(User $user, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('pegass', $csrf);

        if ($user->isEqualTo($this->getUser())) {
            throw $this->createNotFoundException();
        }

        $old = $this->userAuditLogManager->buildSnapshot($user);
        $user->setIsVerified(1 - $user->isVerified());
        $this->userManager->save($user);
        $this->userAuditLogManager->logUpdated($this->resolveActor(), null, $user, $old);

        return $this->redirectToRoute('admin_redcall_users_index', [
            'form[criteria]' => $user->getExternalId(),
        ]);
    }

    #[Route(name: "toggle_trust", path: "/toggle-trust/{csrf}/{id}")]
#[IsGranted("USER", subject: "user")]
    public function toggleTrustAction(User $user, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('pegass', $csrf);

        if ($user->isEqualTo($this->getUser())) {
            throw $this->createNotFoundException();
        }

        $old = $this->userAuditLogManager->buildSnapshot($user);
        $user->setIsTrusted(1 - $user->isTrusted());
        $this->userManager->save($user);
        $this->userAuditLogManager->logUpdated($this->resolveActor(), null, $user, $old);

        return $this->redirectToRoute('admin_redcall_users_index', [
            'form[criteria]' => $user->getExternalId(),
        ]);
    }

    #[Route(name: "toggle_admin", path: "/toggle-admin/{csrf}/{id}")]
#[IsGranted("USER", subject: "user")]
    public function toggleAdminAction(User $user, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('pegass', $csrf);

        if ($user->isEqualTo($this->getUser())) {
            throw $this->createNotFoundException();
        }

        $old = $this->userAuditLogManager->buildSnapshot($user);
        $user->setIsAdmin(1 - $user->isAdmin());
        $this->userManager->save($user);
        $this->userAuditLogManager->logUpdated($this->resolveActor(), null, $user, $old);

        return $this->redirectToRoute('admin_redcall_users_index', [
            'form[criteria]' => $user->getExternalId(),
        ]);
    }

    #[Route(name: "toggle_lock", path: "/toggle-lock/{csrf}/{id}")]
#[IsGranted("USER", subject: "user")]
    public function toggleLockAction(User $user, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('pegass', $csrf);

        if ($user->isEqualTo($this->getUser())) {
            throw $this->createNotFoundException();
        }

        $old = $this->userAuditLogManager->buildSnapshot($user);
        $user->setLocked(1 - $user->isLocked());
        $this->userManager->save($user);
        $this->userAuditLogManager->logUpdated($this->resolveActor(), null, $user, $old);

        return $this->redirectToRoute('admin_redcall_users_index', [
            'form[criteria]' => $user->getExternalId(),
        ]);
    }

    #[Route(name: "toggle_root", path: "/toggle-root/{csrf}/{id}")]
#[IsGranted("ROLE_ROOT")]
#[IsGranted("USER", subject: "user")]
    public function toggleRootAction(User $user, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('pegass', $csrf);

        if ($user->isEqualTo($this->getUser())) {
            throw $this->createNotFoundException();
        }

        $old = $this->userAuditLogManager->buildSnapshot($user);
        $user->setIsRoot(1 - $user->isRoot());
        if ($user->isRoot()) {
            $user->setIsAdmin(true);
        }

        $this->userManager->save($user);
        $this->userAuditLogManager->logUpdated($this->resolveActor(), null, $user, $old);

        return $this->redirectToRoute('admin_redcall_users_index', [
            'form[criteria]' => $user->getExternalId(),
        ]);
    }

    #[Route(name: "delete", path: "/delete/{csrf}/{id}")]
#[IsGranted("USER", subject: "user")]
    public function deleteAction(User $user, $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('pegass', $csrf);

        if ($user->isEqualTo($this->getUser())) {
            throw $this->createNotFoundException();
        }

        $snapshot = $this->userAuditLogManager->buildSnapshot($user);
        $this->userManager->remove($user);
        $this->userAuditLogManager->logDeleted($this->resolveActor(), null, $snapshot);

        return $this->redirectToRoute('admin_redcall_users_index');
    }

    #[Route(path: "/administrators", name: "administrators")]
    public function administrators(Request $request)
    {
        $users = $this->userManager->searchQueryBuilder(null, true, false)
                                   ->andWhere('u.isRoot = false')
                                   ->getQuery()
                                   ->getResult();

        $roots = $this->userManager->searchQueryBuilder(null, true, false)
                                   ->andWhere('u.isRoot = true')
                                   ->getQuery()
                                   ->getResult();

        return $this->render('admin/users/administrators.html.twig', [
            'users' => $users,
            'roots' => $roots,
        ]);
    }

    #[Route("/revoke-admin/{csrf}/{id}", name: "revoke_admin")]
#[IsGranted("ROLE_ROOT")]
    public function revokeAdmin(User $user, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('pegass', $csrf);

        if ($user->isEqualTo($this->getUser())) {
            throw $this->createNotFoundException();
        }

        if ($user->isRoot()) {
            throw $this->createAccessDeniedException('Cannot revoke root admin');
        }

        $old = $this->userAuditLogManager->buildSnapshot($user);
        $user->setIsAdmin(false);
        $this->userManager->save($user);
        $this->userAuditLogManager->logUpdated($this->resolveActor(), null, $user, $old);

        return $this->redirectToRoute('admin_redcall_users_administrators');
    }

    #[Route(path: "/rtmr", name: "rtmr")]
    public function rtmr(Request $request)
    {
        $badge = $this->badgeManager->findOneByName(\App\Sync\Reconciliation\RtmrReconciliator::RTMR_BADGE);

        if (!$badge) {
            $volunteers = [];
        } else {
            $volunteers = $this->volunteerManager->getVolunteersHavingBadgeQueryBuilder($badge)
                                                 ->join('v.user', 'u')
                                                 ->addSelect('u')
                                                 ->getQuery()
                                                 ->getResult();
        }

        return $this->render('admin/users/rtmr.html.twig', [
            'volunteers' => $volunteers,
        ]);
    }

    private function resolveActor() : ?User
    {
        $user = $this->getUser();

        return $user instanceof User ? $user : null;
    }

    private function createSearchForm(Request $request)
    {
        return $this
            ->createFormBuilder(null, ['csrf_protection' => false])
            ->setMethod('GET')
            ->add('criteria', TextType::class, [
                'label'    => 'password_login.user_list.search.criteria',
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'password_login.user_list.search.submit',
            ])
            ->getForm()
            ->handleRequest($request);
    }
}