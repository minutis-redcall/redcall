<?php

namespace App\Controller\Management\Structure;

use App\Base\BaseController;
use App\Entity\Structure;
use App\Entity\Volunteer;
use App\Entity\VolunteerList;
use App\Form\Type\VolunteerListType;
use App\Manager\AudienceManager;
use App\Manager\VolunteerListManager;
use App\Manager\VolunteerManager;
use App\Model\Csrf;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="management/structures/volunteer-lists/{structureId}", name="management_structures_volunteer_lists_")
 * @Entity("structure", expr="repository.find(structureId)")
 * @IsGranted("STRUCTURE", subject="structure")
 */
class VolunteerListController extends BaseController
{
    /**
     * @var AudienceManager
     */
    private $audienceManager;

    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @var VolunteerListManager
     */
    private $volunteerListManager;

    public function __construct(AudienceManager $audienceManager,
        VolunteerManager $volunteerManager,
        VolunteerListManager $volunteerListManager)
    {
        $this->audienceManager      = $audienceManager;
        $this->volunteerManager     = $volunteerManager;
        $this->volunteerListManager = $volunteerListManager;
    }

    /**
     * @Route("/", name="index")
     */
    public function indexAction(Structure $structure)
    {
        return $this->render('management/structures/volunteer_list/index.html.twig', [
            'structure' => $structure,
        ]);
    }

    /**
     * @Route("/create/{volunteerListId}", name="create", defaults={"volunteerListId"=null})
     * @Entity("volunteerList", expr="repository.findOneById(volunteerListId)")
     */
    public function createAction(Structure $structure, Request $request, ?VolunteerList $volunteerList = null)
    {
        $volunteerList = $volunteerList ?? new VolunteerList();
        $volunteerList->setStructure($structure);
        $volunteerList->setAudience([
            'volunteers' => array_map(function (Volunteer $volunteer) {
                return $volunteer->getId();
            }, $volunteerList->getVolunteers()->toArray()),
        ]);

        $form = $this
            ->createForm(VolunteerListType::class, $volunteerList ?? new VolunteerList())
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $classification = $this->audienceManager->classifyAudience($this->getPlatform(), $form->get('audience')->getData());
            $volunteers     = $this->volunteerManager->getVolunteerList($this->getPlatform(), $classification->getReachable());

            $volunteerList->getVolunteers()->clear();
            foreach ($volunteers as $volunteer) {
                $volunteerList->addVolunteer($volunteer);
            }

            $this->volunteerListManager->save($volunteerList);

            return $this->redirectToRoute('management_structures_volunteer_lists_index', [
                'structureId' => $structure->getId(),
            ]);
        }

        return $this->render('management/structures/volunteer_list/create.html.twig', [
            'structure' => $structure,
            'list'      => $volunteerList,
            'form'      => $form->createView(),
        ]);
    }

    /**
     * @Route("/remove/{csrf}/{volunteerListId}", name="delete")
     * @Entity("volunteerList", expr="repository.findOneById(volunteerListId)")
     */
    public function deleteAction(Structure $structure, VolunteerList $volunteerList, Csrf $csrf)
    {
        $this->volunteerListManager->remove($volunteerList);

        return $this->redirectToRoute('management_structures_volunteer_lists_index', [
            'structureId' => $structure->getId(),
        ]);
    }
}