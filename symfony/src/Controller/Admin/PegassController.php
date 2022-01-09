<?php

namespace App\Controller\Admin;

use App\Base\BaseController;
use App\Entity\Structure;
use App\Entity\User;
use App\Form\Type\ManageUserStructuresType;
use App\Form\Type\VolunteerWidgetType;
use App\Manager\PlatformConfigManager;
use App\Manager\StructureManager;
use App\Manager\UserManager;
use App\Manager\VolunteerManager;
use App\Model\Csrf;
use App\Model\PlatformConfig;
use Bundles\PaginationBundle\Manager\PaginationManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="admin/pegass", name="admin_pegass_")
 */
class PegassController extends BaseController
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
     * @var PlatformConfigManager
     */
    private $platformManager;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(UserManager $userManager,
        StructureManager $structureManager,
        VolunteerManager $volunteerManager,
        PaginationManager $paginationManager,
        PlatformConfigManager $platformManager,
        RequestStack $requestStack)
    {
        $this->userManager       = $userManager;
        $this->structureManager  = $structureManager;
        $this->volunteerManager  = $volunteerManager;
        $this->paginationManager = $paginationManager;
        $this->platformManager   = $platformManager;
        $this->requestStack      = $requestStack;
    }

    /**
     * @Route(name="index")
     */
    public function index()
    {
        $request = $this->requestStack->getMasterRequest();
        $search  = $this->createSearchForm($request);

        if ($search->isSubmitted() && $search->isValid()) {
            $criteria       = $search->get('criteria')->getData();
            $onlyAdmins     = $search->get('only_admins')->getData();
            $onlyDevelopers = $search->get('only_developers')->getData();
        }

        return $this->render('admin/pegass/index.html.twig', [
            'search'    => $search->createView(),
            'type'      => $request->get('type'),
            'users'     => $this->paginationManager->getPager(
                $this->userManager->searchQueryBuilder($criteria ?? null, $onlyAdmins ?? false, $onlyDevelopers ?? false)
            ),
            'platforms' => $this->platformManager->getAvailablePlatforms(),
        ]);
    }

    /**
     * @Route(name="list_users", path="/list-users")
     */
    public function userList()
    {
        $users = $this->userManager->findAll();

        $list = array_filter(array_map(function (User $user) {
            return $user->getExternalId();
        }, $users));

        return $this->render('admin/pegass/user_list.html.twig', [
            'list' => $list,
        ]);
    }

    /**
     * @Route(name="update", path="/update/{csrf}/{id}")
     * @IsGranted("USER", subject="user")
     */
    public function updateBoundVolunteer(Request $request, string $csrf, User $user)
    {
        $this->validateCsrfOrThrowNotFoundException('pegass', $csrf);

        $externalId = $request->request->get('externalId');

        if (!$user->isLocked()) {
            $this->userManager->changeVolunteer($user, $this->getPlatform(), $externalId);
        }

        $structureNames = array_filter(array_map(function (Structure $structure) {
            return $structure->getName();
        }, $user->getStructures()->toArray()));

        return $this->json([
            'structures' => array_map('htmlentities', $structureNames),
        ]);
    }

    /**
     * @Route(name="update_structures", path="/update-structures/{id}")
     * @IsGranted("USER", subject="user")
     */
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
            foreach ($clone->getStructures(false) as $structure) {
                $user->removeStructure($structure);
            }

            // Freeze user to keep prevent Pegass from overwriting the change
            $user->setLocked(true);
            $this->userManager->save($user);

            return $this->redirectToRoute('admin_pegass_update_structures', [
                'id' => $user->getId(),
            ]);
        }

        return $this->render('admin/pegass/structures.html.twig', [
            'user'       => $user,
            'form'       => $form->createView(),
            'structures' => $this->structureManager->getStructuresForUser($this->getPlatform(), $user),
        ]);
    }

    /**
     * @Route(name="add_structure", path="/add-structure/{csrf}/{id}")
     * @IsGranted("USER", subject="user")
     */
    public function addStructure(Request $request, string $csrf, User $user)
    {
        $this->validateCsrfOrThrowNotFoundException('pegass', $csrf);

        $structureId = $request->get('structure');
        if (!$structureId) {
            throw $this->createNotFoundException();
        }

        $parentStructure = $this->structureManager->find($structureId);
        if (!$parentStructure) {
            throw $this->createNotFoundException();
        }

        $structures = $this->structureManager->findCallableStructuresForStructure($this->getPlatform(), $parentStructure);
        foreach ($structures as $structure) {
            $user->addStructure($structure);
        }

        // Freeze user to keep prevent Pegass from overwriting the change
        $user->setLocked(true);

        $this->userManager->save($user);

        return $this->redirectToRoute('admin_pegass_update_structures', [
            'id' => $user->getId(),
        ]);
    }

    /**
     * @Route(name="create_user", path="/create-user")
     */
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
                $this->getPlatform(),
                $form->get('externalId')->getData()
            );

            if (!$volunteer) {
                throw $this->createNotFoundException();
            }

            $this->userManager->createUser($this->getPlatform(), $volunteer->getExternalId());

            return $this->redirectToRoute('admin_pegass_index', [
                'form[criteria]' => $volunteer->getExternalId(),
            ]);
        }

        return $this->render('admin/pegass/create_user.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route(name="toggle_verify", path="/toggle-verify/{csrf}/{id}")
     * @IsGranted("USER", subject="user")
     */
    public function toggleVerifyAction(User $user, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('pegass', $csrf);

        if ($user->isEqualTo($this->getUser())) {
            throw $this->createNotFoundException();
        }

        $user->setIsVerified(1 - $user->isVerified());
        $this->userManager->save($user);

        return $this->redirectToRoute('admin_pegass_index', [
            'form[criteria]' => $user->getExternalId(),
        ]);
    }

    /**
     * @Route(name="toggle_trust", path="/toggle-trust/{csrf}/{id}")
     * @IsGranted("USER", subject="user")
     */
    public function toggleTrustAction(User $user, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('pegass', $csrf);

        if ($user->isEqualTo($this->getUser())) {
            throw $this->createNotFoundException();
        }

        $user->setIsTrusted(1 - $user->isTrusted());
        $this->userManager->save($user);

        return $this->redirectToRoute('admin_pegass_index', [
            'form[criteria]' => $user->getExternalId(),
        ]);
    }

    /**
     * @Route(name="toggle_admin", path="/toggle-admin/{csrf}/{id}")
     * @IsGranted("USER", subject="user")
     */
    public function toggleAdminAction(User $user, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('pegass', $csrf);

        if ($user->isEqualTo($this->getUser())) {
            throw $this->createNotFoundException();
        }

        $user->setIsAdmin(1 - $user->isAdmin());
        $this->userManager->save($user);

        return $this->redirectToRoute('admin_pegass_index', [
            'form[criteria]' => $user->getExternalId(),
        ]);
    }

    /**
     * @Route(name="toggle_lock", path="/toggle-lock/{csrf}/{id}")
     * @IsGranted("USER", subject="user")
     */
    public function toggleLockAction(User $user, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('pegass', $csrf);

        if ($user->isEqualTo($this->getUser())) {
            throw $this->createNotFoundException();
        }

        $user->setLocked(1 - $user->isLocked());
        $this->userManager->save($user);

        return $this->redirectToRoute('admin_pegass_index', [
            'form[criteria]' => $user->getExternalId(),
        ]);
    }

    /**
     * @Route(name="toggle_developer", path="/toggle-developer/{csrf}/{id}")
     * @IsGranted("USER", subject="user")
     */
    public function toggleDeveloperAction(User $user, $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('pegass', $csrf);

        if ($user->isEqualTo($this->getUser())) {
            throw $this->createNotFoundException();
        }

        $user->setIsDeveloper(1 - $user->isDeveloper());
        $this->userManager->save($user);

        return $this->redirectToRoute('admin_pegass_index', [
            'form[criteria]' => $user->getExternalId(),
        ]);
    }

    /**
     * @Route(name="toggle_root", path="/toggle-root/{csrf}/{id}")
     * @IsGranted("ROLE_ROOT")
     * @IsGranted("USER", subject="user")
     */
    public function toggleRootAction(User $user, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('pegass', $csrf);

        if ($user->isEqualTo($this->getUser())) {
            throw $this->createNotFoundException();
        }

        $user->setIsRoot(1 - $user->isRoot());
        $this->userManager->save($user);

        return $this->redirectToRoute('admin_pegass_index', [
            'form[criteria]' => $user->getExternalId(),
        ]);
    }

    /**
     * @Route(name="toggle_pegass_api", path="/toggle-pegass-api/{csrf}/{id}")
     * @IsGranted("USER", subject="user")
     */
    public function togglePegassApiAction(User $user, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('pegass', $csrf);

        if (!$this->getUser()->canGrantPegassApi()) {
            throw $this->createNotFoundException();
        }

        $user->setIsPegassApi(1 - $user->isPegassApi());
        $this->userManager->save($user);

        return $this->redirectToRoute('admin_pegass_index', [
            'form[criteria]' => $user->getExternalId(),
        ]);
    }

    /**
     * @Route(name="delete", path="/delete/{csrf}/{id}")
     * @IsGranted("USER", subject="user")
     */
    public function deleteAction(User $user, $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('pegass', $csrf);

        if ($user->isEqualTo($this->getUser())) {
            throw $this->createNotFoundException();
        }

        $this->userManager->remove($user);

        return $this->redirectToRoute('admin_pegass_index');
    }

    /**
     * @Route(name="update_platform", path="/change-platform/{csrf}/{id}/{platform}")
     * @IsGranted("ROLE_ROOT")
     * @IsGranted("USER", subject="user")
     */
    public function changePlatform(User $user, Csrf $csrf, PlatformConfig $platform)
    {
        if ($user->isEqualTo($this->getUser())) {
            throw $this->createNotFoundException();
        }

        $user->setPlatform($platform);

        $this->userManager->save($user);

        return $this->redirectToRoute('admin_pegass_index', [
            'form[criteria]' => $user->getExternalId(),
        ]);
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
            ->add('only_admins', CheckboxType::class, [
                'label'    => 'admin.pegass.only_admins',
                'required' => false,
            ])
            ->add('only_developers', CheckboxType::class, [
                'label'    => 'admin.pegass.only_developers',
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'password_login.user_list.search.submit',
            ])
            ->getForm()
            ->handleRequest($request);
    }
}