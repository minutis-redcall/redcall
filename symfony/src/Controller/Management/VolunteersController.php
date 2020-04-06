<?php

namespace App\Controller\Management;

use App\Base\BaseController;
use App\Entity\Structure;
use App\Entity\Volunteer;
use App\Form\Type\CSVImportType;
use App\Form\Type\VolunteerType;
use App\Import\Result;
use App\Import\VolunteerImporter;
use App\Manager\StructureManager;
use App\Manager\UserInformationManager;
use App\Manager\VolunteerManager;
use Bundles\PaginationBundle\Manager\PaginationManager;
use Bundles\PegassCrawlerBundle\Entity\Pegass;
use Bundles\PegassCrawlerBundle\Manager\PegassManager;
use DateTime;
use DateTimeZone;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @Route(path="management/volunteers", name="management_volunteers_")
 */
class VolunteersController extends BaseController
{
    /**
     * @var UserInformationManager
     */
    private $userInformationManager;

    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @var StructureManager
     */
    private $structureManager;

    /**
     * @var PegassManager
     */
    private $pegassManager;

    /**
     * @var PaginationManager
     */
    private $paginationManager;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var VolunteerImporter
     */
    private $volunteerImporter;

    /**
     * @param UserInformationManager $userInformationManager
     * @param VolunteerManager       $volunteerManager
     * @param StructureManager       $structureManager
     * @param PegassManager          $pegassManager
     * @param PaginationManager      $paginationManager
     * @param KernelInterface        $kernel
     * @param VolunteerImporter      $volunteerImporter
     */
    public function __construct(UserInformationManager $userInformationManager,
        VolunteerManager $volunteerManager,
        StructureManager $structureManager,
        PegassManager $pegassManager,
        PaginationManager $paginationManager,
        KernelInterface $kernel,
        VolunteerImporter $volunteerImporter
    )
    {
        $this->userInformationManager = $userInformationManager;
        $this->volunteerManager       = $volunteerManager;
        $this->structureManager       = $structureManager;
        $this->pegassManager          = $pegassManager;
        $this->paginationManager      = $paginationManager;
        $this->kernel                 = $kernel;
        $this->volunteerImporter      = $volunteerImporter;
    }

    /**
     * @Route(name="list")
     */
    public function listAction(Request $request)
    {
        // CSV import form.
        $errors = [];
        $importForm = $this->createForm(CSVImportType::class);
        $importForm->handleRequest($request);
        if ($this->isGranted('ROLE_ADMIN') && !getenv('IS_REDCROSS')
            && $importForm->isSubmitted() && $importForm->isValid()) {

            /** @var UploadedFile $file */
            $file = $importForm->get('file')->getData();

            try {
                $result = $this->volunteerImporter->import($file);
                $errors = $result->getErrors();

                if ($result->getStatus() === Result::STATUS_COMPLETED) {
                    $this->info($this->trans('import.completed', [
                        '%nbImportedLines%' => $result->getNbImportedLines(),
                        '%nbIgnoredLines%' => $result->getNbIgnoredLines(),
                    ]));
                }
            } catch (\Exception $e) {
                $this->danger($this->trans('import.error', [
                    '%fileName%' => $file->getFilename(),
                ]));

                return $this->redirectToRoute('management_volunteers_list');
            }
        }

        $search = $this->createSearchForm($request);

        $criteria = null;
        if ($search->isSubmitted() && $search->isValid()) {
            $criteria = $search->get('criteria')->getData();
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            $queryBuilder = $this->volunteerManager->searchAllQueryBuilder($criteria);
        } else {
            $queryBuilder = $this->volunteerManager->searchForCurrentUserQueryBuilder($criteria);
        }

        return $this->render('management/volunteers/list.html.twig', [
            'search'     => $search->createView(),
            'volunteers' => $this->paginationManager->getPager($queryBuilder),
            'importForm' => $importForm->createView(),
            'errors' => $errors,
        ]);
    }

    /**
     * @Route(path="/pegass-update/{csrf}/{id}", name="pegass_update")
     * @IsGranted("VOLUNTEER", subject="volunteer")
     */
    public function pegassUpdate(Volunteer $volunteer, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('volunteers', $csrf);

        if (!getenv('IS_REDCROSS')) {
            throw $this->createNotFoundException();
        }

        if (!$volunteer->canForcePegassUpdate()) {
            return $this->redirectToRoute('management_volunteers_list');
        }

        // Just in case Pegass database would contain some RCE?
        if (!preg_match('/^[a-zA-Z0-9]+$/', $volunteer->getIdentifier())) {
            return $this->redirectToRoute('management_volunteers_list');
        }

        // Prevents multiple clicks
        $volunteer->setLastPegassUpdate(new DateTime('now', new DateTimeZone('UTC')));
        $this->volunteerManager->save($volunteer);

        // Executing asynchronous task to prevent against interruptions
        $console = sprintf('%s/../bin/console', $this->kernel->getRootDir());
        $command = sprintf('%s pegass --volunteer %s', escapeshellarg($console), $volunteer->getIdentifier());
        exec(sprintf('%s > /dev/null 2>&1 & echo -n \$!', $command));

        return $this->redirectToRoute('management_volunteers_list');
    }

    /**
     * @Route(path="/manual-update/{id}", name="manual_update")
     * @IsGranted("VOLUNTEER", subject="volunteer")
     */
    public function manualUpdateAction(Request $request, Volunteer $volunteer)
    {
        $isCreate = !$volunteer->getId();

        $form = $this
            ->createForm(VolunteerType::class, $volunteer)
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Locks volunteer from being removed at next Pegass sync
            $volunteer->setLocked(true);

            // We should not trigger Pegass updates on a volunteer not taken from Pegass
            if (!$volunteer->getId()) {
                $volunteer->setLastPegassUpdate(new \DateTime('2100-12-31'));
            }

            $this->volunteerManager->save($volunteer);

            if ($isCreate) {
                $this->success('manage_volunteers.form.added');
            } else {
                $this->success('manage_volunteers.form.updated');
            }

            if ($isCreate && $this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('management_volunteers_edit_structures', [
                    'id' => $volunteer->getId(),
                ]);
            }

            return $this->redirectToRoute('management_volunteers_list');
        }

        return $this->render('management/volunteers/form.html.twig', [
            'form'      => $form->createView(),
            'isCreate'  => $isCreate,
            'volunteer' => $volunteer,
        ]);
    }

    /**
     * @Route(path="/create", name="create")
     */
    public function createAction(Request $request)
    {
        return $this->manualUpdateAction($request, new Volunteer());
    }

    /**
     * @Route(path="/lock/{csrf}/{id}", name="lock")
     * @IsGranted("VOLUNTEER", subject="volunteer")
     */
    public function lockAction(Volunteer $volunteer, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('volunteers', $csrf);

        $volunteer->setLocked(true);
        $this->volunteerManager->save($volunteer);

        return $this->redirectToRoute('management_volunteers_list');
    }

    /**
     * @Route(path="/unlock/{csrf}/{id}", name="unlock")
     * @IsGranted("VOLUNTEER", subject="volunteer")
     */
    public function unlockAction(Volunteer $volunteer, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('volunteers', $csrf);

        $volunteer->setLocked(false);
        $this->volunteerManager->save($volunteer);

        return $this->redirectToRoute('management_volunteers_list');
    }

    /**
     * @Route(path="/disable/{csrf}/{id}", name="disable")
     * @IsGranted("VOLUNTEER", subject="volunteer")
     */
    public function disableAction(Volunteer $volunteer, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('volunteers', $csrf);

        $volunteer->setEnabled(false);
        $volunteer->setLocked(true);
        $this->volunteerManager->save($volunteer);

        return $this->redirectToRoute('management_volunteers_list');
    }

    /**
     * @Route(path="/enable/{csrf}/{id}", name="enable")
     * @IsGranted("VOLUNTEER", subject="volunteer")
     */
    public function enableAction(Volunteer $volunteer, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('volunteers', $csrf);

        $volunteer->setEnabled(true);
        $volunteer->setLocked(true);
        $this->volunteerManager->save($volunteer);

        return $this->redirectToRoute('management_volunteers_list');
    }

    /**
     * @Route(path="/pegass/{id}", name="pegass")
     * @IsGranted("ROLE_ADMIN")
     */
    public function pegass(Volunteer $volunteer)
    {
        if (!getenv('IS_REDCROSS')) {
            throw $this->createNotFoundException();
        }

        $entity = $this->pegassManager->getEntity(Pegass::TYPE_VOLUNTEER, $volunteer->getIdentifier(), false);
        if (!$entity) {
            throw $this->createNotFoundException();
        }

        return $this->render('management/volunteers/pegass.html.twig', [
            'volunteer' => $volunteer,
            'pegass'    => json_encode($entity->getContent(), JSON_PRETTY_PRINT),
        ]);
    }

    /**
     * @Route(path="/edit-structures/{id}", name="edit_structures")
     * @IsGranted("ROLE_ADMIN")
     */
    public function editStructures(Volunteer $volunteer)
    {
        return $this->render('management/volunteers/structures.html.twig', [
            'volunteer' => $volunteer,
        ]);
    }

    /**
     * @Route(path="/add-structure/{csrf}/{id}", name="add_structure")
     * @IsGranted("ROLE_ADMIN")
     */
    public function addStructure(Request $request, string $csrf, Volunteer $volunteer)
    {
        $this->validateCsrfOrThrowNotFoundException('volunteer', $csrf);

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
            $volunteer->addStructure($structure);
        }

        $this->volunteerManager->save($volunteer);

        return $this->redirectToRoute('management_volunteers_edit_structures', [
            'id' => $volunteer->getId(),
        ]);
    }

    /**
     * @Route(path="/delete-structure/{csrf}/{volunteerId}/{structureId}", name="delete_structure")
     * @Entity("volunteer", expr="repository.find(volunteerId)")
     * @Entity("structure", expr="repository.find(structureId)")
     */
    public function deleteStructure(string $csrf, Volunteer $volunteer, Structure $structure)
    {
        $this->validateCsrfOrThrowNotFoundException('volunteer', $csrf);

        if (0 !== $structure->getIdentifier()) {
            $volunteer->removeStructure($structure);

            $this->volunteerManager->save($volunteer);
        }

        return $this->redirectToRoute('management_volunteers_edit_structures', [
            'id' => $volunteer->getId(),
        ]);
    }

    /**
     * @param Request $request
     *
     * @return FormInterface
     */
    private function createSearchForm(Request $request): FormInterface
    {
        return $this->createFormBuilder(null, ['csrf_protection' => false])
                    ->setMethod('GET')
                    ->add('criteria', TextType::class, [
                        'label'    => 'manage_volunteers.search.label',
                        'required' => false,
                    ])
                    ->add('submit', SubmitType::class, [
                        'label' => 'manage_volunteers.search.button',
                    ])
                    ->getForm()
                    ->handleRequest($request);
    }
}
