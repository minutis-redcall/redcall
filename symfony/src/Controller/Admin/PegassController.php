<?php

namespace App\Controller\Admin;

use App\Base\BaseController;
use App\Entity\Structure;
use App\Entity\UserInformation;
use App\Manager\StructureManager;
use App\Manager\UserInformationManager;
use Bundles\PaginationBundle\Manager\PaginationManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
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
     * @var UserInformationManager
     */
    private $userInformationManager;

    /**
     * @var StructureManager
     */
    private $structureManager;

    /**
     * @var PaginationManager
     */
    private $paginationManager;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @param UserInformationManager $userInformationManager
     * @param StructureManager       $structureManager
     * @param PaginationManager      $paginationManager
     * @param RequestStack           $requestStack
     */
    public function __construct(UserInformationManager $userInformationManager, StructureManager $structureManager, PaginationManager $paginationManager, RequestStack $requestStack)
    {
        $this->userInformationManager = $userInformationManager;
        $this->structureManager = $structureManager;
        $this->paginationManager = $paginationManager;
        $this->requestStack = $requestStack;
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

        $this->userInformationManager->updateNivol($userInformation, $nivol);

        $structureNames = array_map(function (Structure $structure) {
            return $structure->getName();
        }, $userInformation->getStructures()->toArray());

        return $this->json([
            'structures' => array_map('htmlentities', $structureNames),
        ]);
    }

    /**
     * @Route(name="update_structures", path="/update-structures/{id}")
     */
    public function updateStructures(Request $request, UserInformation $userInformation)
    {
        return $this->render('admin/pegass/structures.html.twig', [
            'user' => $userInformation,
        ]);
    }

    /**
     * @Route(name="add_structure", path="/add-structure/{csrf}{id}")
     */
    public function addStructure(Request $request, string $csrf, UserInformation $userInformation)
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
            $userInformation->addStructure($structure);
        }

        $this->userInformationManager->save($userInformation);

        return $this->redirectToRoute('admin_pegass_update_structures', [
            'id' => $userInformation->getId(),
        ]);
    }

    /**
     * @Route(name="delete_structure", path="/delete-structure/{csrf}/{userInformationId}/{structureId}")
     * @Entity("userInformation", expr="repository.find(userInformationId)")
     * @Entity("structure", expr="repository.find(structureId)")
     */
    public function deleteStructure(Request $request, string $csrf, UserInformation $userInformation, Structure $structure)
    {
        $this->validateCsrfOrThrowNotFoundException('pegass', $csrf);

        $userInformation->removeStructure($structure);

        $this->userInformationManager->save($userInformation);

        return $this->redirectToRoute('admin_pegass_update_structures', [
            'id' => $userInformation->getId(),
        ]);
    }

    private function createSearchForm(Request $request)
    {
        return $this->createFormBuilder(null, ['csrf_protection' => false])
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