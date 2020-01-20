<?php

namespace App\Controller\Admin;

use App\Base\BaseController;
use App\Entity\Structure;
use App\Entity\UserInformation;
use App\Manager\StructureManager;
use App\Manager\UserInformationManager;
use App\Manager\VolunteerManager;
use Bundles\PaginationBundle\Manager\PaginationManager;
use Bundles\PasswordLoginBundle\Manager\UserManager;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="admin/pegass/", name="admin_pegass_")
 */
class PegassController extends BaseController
{
    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @var StructureManager
     */
    private $structureManager;

    /**
     * @var UserInformationManager
     */
    private $userInformationManager;

    /**
     * @var PaginationManager
     */
    private $paginationManager;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @param UserManager            $userManager
     * @param VolunteerManager       $volunteerManager
     * @param StructureManager       $structureManager
     * @param UserInformationManager $userInformationManager
     * @param PaginationManager      $paginationManager
     * @param RequestStack           $requestStack
     */
    public function __construct(UserManager $userManager,
        VolunteerManager $volunteerManager,
        StructureManager $structureManager,
        UserInformationManager $userInformationManager,
        PaginationManager $paginationManager,
        RequestStack $requestStack)
    {
        $this->userManager            = $userManager;
        $this->volunteerManager       = $volunteerManager;
        $this->structureManager       = $structureManager;
        $this->userInformationManager = $userInformationManager;
        $this->paginationManager      = $paginationManager;
        $this->requestStack           = $requestStack;
    }

    public function index()
    {
        $request = $this->requestStack->getMasterRequest();
        $search  = $this->createSearchForm($request);

        $criteria = null;
        if ($search->isSubmitted() && $search->isValid()) {
            $criteria = $search->get('criteria')->getData();
        }

        return $this->render('admin/pegass/index.html.twig', [
            'search'           => $search->createView(),
            'type'             => $request->get('type'),
            'userInformations' => $this->paginationManager->getPager(
                $this->userInformationManager->searchQueryBuilder($criteria)
            ),
        ]);
    }

    /**
     * @Route(name="update", path="/update/{csrf}/{id}")
     */
    public function updateNivol(Request $request, string $csrf, UserInformation $userInformation)
    {
        $this->validateCsrfOrThrowNotFoundException('pegass', $csrf);

        $nivol = $request->request->get('nivol');
        if (!$nivol) {
            return $this->json(null);
        }

        $volunteer = $this->volunteerManager->findOneByNivol($nivol);
        if (!$volunteer) {
            return $this->json(null);
        }

        $userInformation->setNivol($nivol);

        $structures = $this->structureManager->findCallableStructuresForVolunteer($volunteer);
        $userInformation->updateStructures($structures);

        $this->userInformationManager->save($userInformation);

        $structureNames = array_map(function (Structure $structure) {
            return $structure->getName();
        }, $structures);

        return $this->json([
            'structures' => array_map('htmlentities', $structureNames),
        ]);
    }

    private function createSearchForm(Request $request)
    {
        return $this->createFormBuilder()
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