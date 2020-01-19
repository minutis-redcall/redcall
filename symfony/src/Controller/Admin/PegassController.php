<?php

namespace App\Controller\Admin;

use App\Base\BaseController;
use App\Entity\Structure;
use App\Entity\UserInformation;
use App\Manager\StructureManager;
use App\Manager\UserInformationManager;
use App\Manager\VolunteerManager;
use Bundles\PasswordLoginBundle\Manager\UserManager;
use Symfony\Component\HttpFoundation\Request;
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
     * @param UserManager            $userManager
     * @param VolunteerManager       $volunteerManager
     * @param StructureManager       $structureManager
     * @param UserInformationManager $userInformationManager
     */
    public function __construct(UserManager $userManager,
        VolunteerManager $volunteerManager,
        StructureManager $structureManager,
        UserInformationManager $userInformationManager)
    {
        $this->userManager            = $userManager;
        $this->volunteerManager       = $volunteerManager;
        $this->structureManager       = $structureManager;
        $this->userInformationManager = $userInformationManager;
    }

    public function index()
    {
        return $this->render('admin/pegass/index.html.twig', [
            'userInformations' => $this->userInformationManager->findAll(),
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


}