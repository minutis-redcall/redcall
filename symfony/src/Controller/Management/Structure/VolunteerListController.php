<?php

namespace App\Controller\Management\Structure;

use App\Base\BaseController;
use App\Entity\Structure;
use App\Entity\Volunteer;
use App\Entity\VolunteerList;
use App\Form\Type\VolunteerListType;
use App\Form\Type\VolunteerWidgetType;
use App\Manager\AudienceManager;
use App\Manager\VolunteerListManager;
use App\Manager\VolunteerManager;
use App\Model\Csrf;
use App\Security\Helper\Security;
use Bundles\PaginationBundle\Manager\PaginationManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
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

    /**
     * @var PaginationManager
     */
    private $paginationManager;

    /**
     * @var Security
     */
    private $security;

    public function __construct(AudienceManager $audienceManager,
        VolunteerManager $volunteerManager,
        VolunteerListManager $volunteerListManager,
        PaginationManager $paginationManager,
        Security $security)
    {
        $this->audienceManager      = $audienceManager;
        $this->volunteerManager     = $volunteerManager;
        $this->volunteerListManager = $volunteerListManager;
        $this->paginationManager    = $paginationManager;
        $this->security             = $security;
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
     * @Route("/cards/{volunteerListId}", name="cards")
     * @Entity("volunteerList", expr="repository.findOneById(volunteerListId)")
     */
    public function cardsAction(Request $request, Structure $structure, VolunteerList $volunteerList = null)
    {
        $add = $this->createAddVolunteerForm($request);
        if ($add->isSubmitted() && $add->isValid()) {
            $volunteer = $this->volunteerManager->findOneByExternalId($this->getPlatform(), $add->get('externalId')->getData());

            if ($volunteer) {
                $volunteerList->addVolunteer($volunteer);
                $this->volunteerListManager->save($volunteerList);
            }

            return $this->redirectToRoute('management_structures_volunteer_lists_cards', [
                'structureId'     => $structure->getId(),
                'volunteerListId' => $volunteerList->getId(),
            ]);
        }

        $search = $this->createSearchForm($request, $volunteerList);

        $criteria     = null;
        $hideDisabled = true;
        $filterUsers  = false;
        $filterLocked = false;
        $structures   = [];
        if ($search->isSubmitted() && $search->isValid()) {
            $criteria     = $search->get('criteria')->getData();
            $hideDisabled = $search->get('only_enabled')->getData();
            $filterUsers  = $search->get('only_users')->getData();
            $filterLocked = $search->get('only_locked')->getData();
            $structures   = $search->get('structures')->getData();
        }

        $queryBuilder = $this->volunteerManager->getVolunteersFromList(
            $volunteerList,
            $criteria,
            $hideDisabled,
            $filterUsers,
            $filterLocked,
            $structures
        );

        return $this->render('management/structures/volunteer_list/cards.html.twig', [
            'list'       => $volunteerList,
            'structure'  => $structure,
            'volunteers' => $this->paginationManager->getPager($queryBuilder),
            'search'     => $search->createView(),
            'add'        => $add->createView(),
        ]);
    }

    /**
     * @Route("/remove-one-volunteer/{csrf}/{volunteerListId}/{volunteerId}", name="delete_one_volunteer")
     * @Entity("volunteerList", expr="repository.findOneById(volunteerListId)")
     * @Entity("volunteer", expr="repository.findOneById(volunteerId)")
     */
    public function deleteOneVolunteerAction(Structure $structure,
        VolunteerList $volunteerList,
        Volunteer $volunteer,
        Csrf $csrf)
    {
        $volunteerList->removeVolunteer($volunteer);
        $this->volunteerListManager->save($volunteerList);

        return $this->redirectToRoute('management_structures_volunteer_lists_cards', [
            'structureId'     => $volunteerList->getStructure()->getId(),
            'volunteerListId' => $volunteerList->getId(),
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

    private function createSearchForm(Request $request, VolunteerList $volunteerList) : FormInterface
    {
        $currentUser = $this->security->getUser();

        // Create an array of choices for the structure filter, it contains all structures
        // from all volunteers ordered by the number of volunteers in each structure, descending.
        $structures = [];
        $counts     = [];
        foreach ($volunteerList->getVolunteers() as $volunteer) {
            foreach ($volunteer->getStructures() as $structure) {
                $structures[$structure->getId()] = $structure;
                if (!isset($counts[$structure->getId()])) {
                    $counts[$structure->getId()] = 0;
                }
                $counts[$structure->getId()]++;
            }
        }
        arsort($counts);
        $choices = [];
        foreach ($counts as $structureId => $count) {
            $choices[sprintf('%s (%d)', $structures[$structureId]->getName(), $count)] = $structureId;
        }

        return $this
            ->createFormBuilder([
                'only_enabled'      => true,
                'include_hierarchy' => true,
            ], [
                'csrf_protection' => false,
            ])
            ->setMethod('GET')
            ->add('criteria', TextType::class, [
                'label'    => 'manage_volunteers.search.label',
                'required' => false,
            ])
            ->add('only_enabled', CheckboxType::class, [
                'label'    => 'manage_volunteers.search.only_enabled',
                'required' => false,
            ])
            ->add('only_locked', CheckboxType::class, [
                'label'    => 'manage_volunteers.search.only_locked',
                'required' => false,
            ])
            ->add('only_users', CheckboxType::class, [
                'label'    => 'manage_volunteers.search.only_users',
                'required' => false,
            ])
            ->add('structures', ChoiceType::class, [
                'label'    => false,
                'choices'  => $choices,
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'manage_volunteers.search.button',
            ])
            ->getForm()
            ->handleRequest($request);
    }

    private function createAddVolunteerForm(Request $request) : FormInterface
    {
        return $this
            ->createNamedFormBuilder('add_volunteer')
            ->add('externalId', VolunteerWidgetType::class, [
                'label' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'base.button.save',
            ])
            ->getForm()
            ->handleRequest($request);
    }
}