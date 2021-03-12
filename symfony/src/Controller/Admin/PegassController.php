<?php

namespace App\Controller\Admin;

use App\Base\BaseController;
use App\Entity\Structure;
use App\Entity\User;
use App\Form\Type\UserStructuresType;
use App\Form\Type\VolunteerWidgetType;
use App\Manager\PlatformConfigManager;
use App\Manager\StructureManager;
use App\Manager\UserManager;
use App\Manager\VolunteerManager;
use Bundles\PaginationBundle\Manager\PaginationManager;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
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

        $platforms = null;
        if ($this->getUser()->isRoot()) {
            $platforms = $this->platformManager->getAvailablePlatforms();
        }

        return $this->render('admin/pegass/index.html.twig', [
            'search' => $search->createView(),
            'type'   => $request->get('type'),
            'users'  => $this->paginationManager->getPager(
                $this->userManager->searchQueryBuilder($criteria ?? null, $onlyAdmins ?? false, $onlyDevelopers ?? false)
            ),
        ]);
    }

    /**
     * @Route(name="list_users", path="/list-users")
     */
    public function userList()
    {
        $users = $this->userManager->findAll();

        $list = array_filter(array_map(function (User $user) {
            return $user->getNivol();
        }, $users));

        return $this->render('admin/pegass/user_list.html.twig', [
            'list' => $list,
        ]);
    }

    /**
     * @Route(name="update", path="/update/{csrf}/{id}")
     */
    public function updateNivol(Request $request, string $csrf, User $user)
    {
        $this->validateCsrfOrThrowNotFoundException('pegass', $csrf);

        $nivol = $request->request->get('nivol');

        if (!$user->isLocked()) {
            $this->userManager->updateNivol($user, $nivol);
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
            ->createForm(UserStructuresType::class, $clone, [
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
            'structures' => $this->structureManager->getStructuresForUser($user),
        ]);
    }

    /**
     * @Route(name="add_structure", path="/add-structure/{csrf}/{id}")
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

        $structures = $this->structureManager->findCallableStructuresForStructure($parentStructure);
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
                     ->add('nivol', VolunteerWidgetType::class, [
                         'label' => false,
                     ])
                     ->add('submit', SubmitType::class, [
                         'label' => 'base.button.create',
                     ])
                     ->getForm()
                     ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $volunteer = $this->volunteerManager->findOneByNivol($form->get('nivol')->getData());
            if (!$volunteer) {
                throw $this->createNotFoundException();
            }

            $application = new Application($kernel);
            $application->setAutoExit(false);

            $input = new ArrayInput([
                'command' => 'user:create',
                'nivol'   => [$volunteer->getNivol()],
            ]);

            $application->run($input, new NullOutput());

            return $this->redirectToRoute('admin_pegass_index', [
                'form[criteria]' => $volunteer->getNivol(),
            ]);
        }

        return $this->render('admin/pegass/create_user.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route(name="submit_user", path="/submit-user/{csrf}")
     */
    public function submitUser(Request $request, KernelInterface $kernel, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('pegass', $csrf);

        $volunteer = $this->volunteerManager->findOneByNivol($request->get('nivol'));
        if (!$volunteer) {
            throw $this->createNotFoundException();
        }

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'user:create',
            'nivol'   => [$volunteer->getNivol()],
        ]);

        $application->run($input, new NullOutput());

        return $this->redirectToRoute('admin_pegass_index', [
            'form[criteria]' => $volunteer->getNivol(),
        ]);
    }

    /**
     * @Route(name="toggle_verify", path="/toggle-verify/{csrf}/{id}")
     */
    public function toggleVerifyAction(User $user, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('pegass', $csrf);

        $user->setIsVerified(1 - $user->isVerified());
        $this->userManager->save($user);

        return $this->redirectToRoute('admin_pegass_index', [
            'form[criteria]' => $user->getNivol(),
        ]);
    }

    /**
     * @Route(name="toggle_trust", path="/toggle-trust/{csrf}/{id}")
     */
    public function toggleTrustAction(User $user, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('pegass', $csrf);

        $user->setIsTrusted(1 - $user->isTrusted());
        $this->userManager->save($user);

        return $this->redirectToRoute('admin_pegass_index', [
            'form[criteria]' => $user->getNivol(),
        ]);
    }

    /**
     * @Route(name="toggle_admin", path="/toggle-admin/{csrf}/{id}")
     */
    public function toggleAdminAction(User $user, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('pegass', $csrf);

        $user->setIsAdmin(1 - $user->isAdmin());
        $this->userManager->save($user);

        return $this->redirectToRoute('admin_pegass_index', [
            'form[criteria]' => $user->getNivol(),
        ]);
    }

    /**
     * @Route(name="toggle_lock", path="/toggle-lock/{csrf}/{id}")
     */
    public function toggleLockAction(User $user, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('pegass', $csrf);

        $user->setLocked(1 - $user->isLocked());
        $this->userManager->save($user);

        return $this->redirectToRoute('admin_pegass_index', [
            'form[criteria]' => $user->getNivol(),
        ]);
    }

    /**
     * @Route(name="toggle_developer", path="/toggle-developer/{csrf}/{id}")
     */
    public function toggleDeveloperAction(User $user, $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('pegass', $csrf);

        $user->setIsDeveloper(1 - $user->isDeveloper());
        $this->userManager->save($user);

        return $this->redirectToRoute('admin_pegass_index', [
            'form[criteria]' => $user->getNivol(),
        ]);
    }

    /**
     * @Route(name="delete", path="/delete/{csrf}/{id}")
     */
    public function deleteAction(User $user, $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('pegass', $csrf);

        $this->userManager->remove($user);

        return $this->redirectToRoute('admin_pegass_index');
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