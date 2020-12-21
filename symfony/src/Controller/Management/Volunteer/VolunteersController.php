<?php

namespace App\Controller\Management\Volunteer;

use App\Base\BaseController;
use App\Communication\Processor\SimpleProcessor;
use App\Entity\Answer;
use App\Entity\Structure;
use App\Entity\User;
use App\Entity\Volunteer;
use App\Form\Model\Campaign;
use App\Form\Model\EmailTrigger;
use App\Form\Model\SmsTrigger;
use App\Form\Type\VolunteerType;
use App\Import\VolunteerImporter;
use App\Manager\AnswerManager;
use App\Manager\CampaignManager;
use App\Manager\CommunicationManager;
use App\Manager\PhoneManager;
use App\Manager\StructureManager;
use App\Manager\VolunteerManager;
use Bundles\PaginationBundle\Manager\PaginationManager;
use Bundles\PegassCrawlerBundle\Entity\Pegass;
use Bundles\PegassCrawlerBundle\Manager\PegassManager;
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * @Route(path="management/volunteers", name="management_volunteers_")
 */
class VolunteersController extends BaseController
{

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
     * @var CampaignManager
     */
    private $campaignManager;

    /**
     * @var CommunicationManager
     */
    private $communicationManager;

    /**
     * @var PhoneManager
     */
    private $phoneManager;

    /**
     * @var AnswerManager
     */
    private $answerManager;

    /**
     * @var PaginationManager
     */
    private $paginationManager;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Environment
     */
    private $templating;

    public function __construct(VolunteerManager $volunteerManager,
        StructureManager $structureManager,
        PegassManager $pegassManager,
        CampaignManager $campaignManager,
        CommunicationManager $communicationManager,
        PhoneManager $phoneManager,
        AnswerManager $answerManager,
        PaginationManager $paginationManager,
        KernelInterface $kernel,
        TranslatorInterface $translator,
        Environment $templating)
    {
        $this->volunteerManager     = $volunteerManager;
        $this->structureManager     = $structureManager;
        $this->pegassManager        = $pegassManager;
        $this->campaignManager      = $campaignManager;
        $this->communicationManager = $communicationManager;
        $this->phoneManager         = $phoneManager;
        $this->answerManager        = $answerManager;
        $this->paginationManager    = $paginationManager;
        $this->kernel               = $kernel;
        $this->translator           = $translator;
        $this->templating           = $templating;
    }

    /**
     * @Route(name="list", path="/{id}", requirements={"id" = "\d+"}, defaults={"id" = null})
     */
    public function listAction(Request $request, Structure $structure = null)
    {
        $search = $this->createSearchForm($request);

        $criteria     = null;
        $hideDisabled = true;
        $filterUsers  = false;
        if ($search->isSubmitted() && $search->isValid()) {
            $criteria     = $search->get('criteria')->getData();
            $hideDisabled = $search->get('only_enabled')->getData();
            $filterUsers  = $search->get('only_users')->getData();
        }

        if ($structure) {
            $queryBuilder = $this->volunteerManager->searchInStructureQueryBuilder($structure, $criteria, $hideDisabled, $filterUsers);
        } elseif ($this->isGranted('ROLE_ADMIN')) {
            $queryBuilder = $this->volunteerManager->searchAllQueryBuilder($criteria, $hideDisabled, $filterUsers);
        } else {
            $queryBuilder = $this->volunteerManager->searchForCurrentUserQueryBuilder($criteria, $hideDisabled, $filterUsers);
        }

        return $this->render('management/volunteers/list.html.twig', [
            'search'     => $search->createView(),
            'volunteers' => $this->paginationManager->getPager($queryBuilder),
            'structure'  => $structure,
        ]);
    }

    /**
     * @Route(path="/pegass-update/{csrf}/{id}", name="pegass_update")
     * @IsGranted("VOLUNTEER", subject="volunteer")
     */
    public function pegassUpdate(Request $request, Volunteer $volunteer, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('volunteers', $csrf);

        if (!$volunteer->canForcePegassUpdate()) {
            return $this->redirectToRoute('management_volunteers_list', $request->query->all());
        }

        // Just in case Pegass database would contain some RCE?
        if (!preg_match('/^[a-zA-Z0-9]+$/', $volunteer->getIdentifier())) {
            return $this->redirectToRoute('management_volunteers_list', $request->query->all());
        }

        // Prevents multiple clicks
        $volunteer->setLastPegassUpdate(new DateTime('now', new DateTimeZone('UTC')));
        $this->volunteerManager->save($volunteer);

        // Executing asynchronous task to prevent against interruptions
        $console = sprintf('%s/../bin/console', $this->kernel->getRootDir());
        $command = sprintf('%s pegass --volunteer %s', escapeshellarg($console), $volunteer->getIdentifier());
        exec(sprintf('%s > /dev/null 2>&1 & echo -n \$!', $command));

        return $this->redirectToRoute('management_volunteers_list', $request->query->all());
    }

    /**
     * @Route(path="/manual-update/{id}", name="manual_update")
     * @IsGranted("VOLUNTEER", subject="volunteer")
     */
    public function manualUpdateAction(Request $request, Volunteer $volunteer)
    {
        $isCreate = !$volunteer->getId();

        $oldVolunteer = clone $volunteer;
        $oldPhone     = $volunteer->getPhone() ? clone $volunteer->getPhone() : null;

        $form = $this
            ->createForm(VolunteerType::class, $volunteer)
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Locks volunteer from being removed at next Pegass sync
            if ($volunteer->shouldBeLocked($oldVolunteer)) {
                $volunteer->setLocked(true);
            }

            // We should not trigger Pegass updates on a volunteer not taken from Pegass
            if (!$volunteer->getId()) {
                $volunteer->setLastPegassUpdate(new \DateTime('2100-12-31'));
            }

            // Automatically lock phone & email if necessary
            if ($oldPhone !== $volunteer->getPhone()) {
                $volunteer->setPhoneNumberLocked(true);
            }
            if ($oldVolunteer->getEmail() !== $volunteer->getEmail()) {
                $volunteer->setEmailLocked(true);
            }

            try {
                $this->volunteerManager->save($volunteer);
                foreach ($volunteer->getPhones() as $phone) {
                    $this->phoneManager->save($phone);
                }
            } catch (UniqueConstraintViolationException $e) {
                // See SpaceController::phone
                $this->alert('base.error');

                return $this->redirectToRoute('management_volunteers_list', $request->query->all());
            }

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

            return $this->redirectToRoute('management_volunteers_list', $request->query->all());
        }

        if (!$isCreate) {
            $delete = $this->createDeletionForm($request, $volunteer);
            if ($delete->isSubmitted() && $delete->isValid()) {
                return $this->redirectToRoute('management_volunteers_delete', [
                    'volunteerId' => $volunteer->getId(),
                    'answerId'    => $delete->get('answer')->getData()->getId(),
                ]);
            }
        }

        return $this->render('management/volunteers/form.html.twig', [
            'form'      => $form->createView(),
            'isCreate'  => $isCreate,
            'volunteer' => $volunteer,
            'delete'    => !$isCreate ? $delete->createView() : null,
            'answerId'  => $request->get('answerId'),
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
    public function lockAction(Request $request, Volunteer $volunteer, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('volunteers', $csrf);

        $volunteer->setLocked(true);
        $this->volunteerManager->save($volunteer);

        return $this->redirectToRoute('management_volunteers_list', $request->query->all());
    }

    /**
     * @Route(path="/unlock/{csrf}/{id}", name="unlock")
     * @IsGranted("VOLUNTEER", subject="volunteer")
     */
    public function unlockAction(Request $request, Volunteer $volunteer, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('volunteers', $csrf);

        $volunteer->setLocked(false);
        $this->volunteerManager->save($volunteer);

        return $this->redirectToRoute('management_volunteers_list', $request->query->all());
    }

    /**
     * @Route(path="/disable/{csrf}/{id}", name="disable")
     * @IsGranted("VOLUNTEER", subject="volunteer")
     */
    public function disableAction(Request $request, Volunteer $volunteer, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('volunteers', $csrf);

        // Do not disable volunteers tied to RedCall users
        if ($volunteer->getUser()) {
            throw $this->createNotFoundException();
        }

        $volunteer->setEnabled(false);
        $volunteer->setLocked(true);
        $this->volunteerManager->save($volunteer);

        return $this->redirectToRoute('management_volunteers_list', $request->query->all());
    }

    /**
     * @Route(path="/enable/{csrf}/{id}", name="enable")
     * @IsGranted("VOLUNTEER", subject="volunteer")
     */
    public function enableAction(Request $request, Volunteer $volunteer, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('volunteers', $csrf);

        // Do not disable volunteers tied to RedCall users
        if ($volunteer->getUser()) {
            throw $this->createNotFoundException();
        }

        $volunteer->setEnabled(true);
        $volunteer->setLocked(true);
        $this->volunteerManager->save($volunteer);

        return $this->redirectToRoute('management_volunteers_list', $request->query->all());
    }

    /**
     * @Route(path="/pegass/{id}", name="pegass")
     * @IsGranted("ROLE_ADMIN")
     */
    public function pegass(Volunteer $volunteer)
    {
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
     * @IsGranted("ROLE_ADMIN")
     */
    public function deleteStructure(string $csrf, Volunteer $volunteer, Structure $structure)
    {
        $this->validateCsrfOrThrowNotFoundException('volunteer', $csrf);

        $volunteer->removeStructure($structure);

        $this->volunteerManager->save($volunteer);

        return $this->redirectToRoute('management_volunteers_edit_structures', [
            'id' => $volunteer->getId(),
        ]);
    }

    /**
     * @Route(path="/delete/{volunteerId}/{answerId}", name="delete")
     * @Entity("volunteer", expr="repository.find(volunteerId)")
     * @Entity("answer", expr="repository.find(answerId)")
     * @Template("management/volunteers/delete.html.twig")
     */
    public function deleteAction(Request $request, SimpleProcessor $processor, Volunteer $volunteer, Answer $answer)
    {
        if ($volunteer->getUser()) {
            throw $this->createNotFoundException();
        }

        $form = $this->createFormBuilder()
                     ->add('cancel', SubmitType::class, [
                         'label' => 'manage_volunteers.anonymize.cancel',
                         'attr'  => [
                             'class' => 'btn btn-success',
                         ],
                     ])
                     ->add('confirm', SubmitType::class, [
                         'label' => 'manage_volunteers.anonymize.confirm',
                         'attr'  => [
                             'class' => 'btn btn-danger',
                         ],
                     ])
                     ->getForm()
                     ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $trigger = $this->deleteVolunteer($volunteer, $answer, $processor);

            return $this->redirectToRoute('communication_index', [
                'id' => $trigger->getId(),
            ]);
        }

        return [
            'volunteer' => $volunteer,
            'answer'    => $answer,
            'form'      => $form->createView(),
        ];
    }

    private function deleteVolunteer(Volunteer $volunteer,
        Answer $answer,
        SimpleProcessor $processor) : \App\Entity\Campaign
    {
        // Sending a message to the volunteer to let him know he is now removed
        $sms      = new SmsTrigger();
        $campaign = new Campaign($sms);

        $campaign->label = $this->translator->trans('manage_volunteers.anonymize.campaign.title', [
            '%nivol%' => $volunteer->getNivol(),
        ]);

        $sms->setAudience([$volunteer->getNivol()]);

        $sms->setMessage(
            $this->translator->trans('manage_volunteers.anonymize.campaign.sms_content')
        );

        $trigger = $this->campaignManager->launchNewCampaign($campaign, $processor);

        // Sending a message inviting redcall users managing volunteer's structure to complete data deletion
        $email = new EmailTrigger();

        $audience            = [];
        $triggeringVolunteer = $answer->getMessage()->getCommunication()->getVolunteer();
        if (!$triggeringVolunteer) {
            return $trigger;
        }

        $commonStructures = array_intersect($triggeringVolunteer->getStructures()->toArray(), $volunteer->getStructures()->toArray());
        foreach ($commonStructures as $structure) {
            /** @var Structure $structure */
            foreach ($structure->getUsers() as $user) {
                /** @var User $user */
                if ($user->getVolunteer()) {
                    $audience[] = $user->getVolunteer()->getNivol();
                }
            }
            if ($structure->getPresident()) {
                $audience[] = $structure->getPresident();
            }
        }
        $email->setAudience(array_unique($audience));

        $email->setSubject($this->translator->trans('manage_volunteers.anonymize.campaign.email.subject', [
            '%nivol%' => $volunteer->getNivol(),
        ]));

        $email->setMessage($this->templating->render('management/volunteers/delete_email.html.twig', [
            'volunteer'   => $volunteer,
            'answer'      => $answer,
            'website_url' => getenv('WEBSITE_URL'),
        ]));

        $this->communicationManager->launchNewCommunication($trigger, $email);

        $this->volunteerManager->anonymize($volunteer);

        return $trigger;
    }

    private function createSearchForm(Request $request) : FormInterface
    {
        return $this->createFormBuilder(['only_enabled' => true], ['csrf_protection' => false])
                    ->setMethod('GET')
                    ->add('criteria', TextType::class, [
                        'label'    => 'manage_volunteers.search.label',
                        'required' => false,
                    ])
                    ->add('only_enabled', CheckboxType::class, [
                        'label'    => 'manage_volunteers.search.only_enabled',
                        'required' => false,
                    ])
                    ->add('only_users', CheckboxType::class, [
                        'label'    => 'manage_volunteers.search.only_users',
                        'required' => false,
                    ])
                    ->add('submit', SubmitType::class, [
                        'label' => 'manage_volunteers.search.button',
                        'attr'  => [
                            'class ' => 'd-none',
                        ],
                    ])
                    ->getForm()
                    ->handleRequest($request);
    }

    private function createDeletionForm(Request $request, Volunteer $volunteer) : FormInterface
    {
        return $this->createFormBuilder()
                    ->add('answer', EntityType::class, [
                        'class'         => Answer::class,
                        'query_builder' => $this->answerManager->getVolunteerAnswersQueryBuilder($volunteer),
                        'choice_label'  => function (Answer $answer) {
                            return sprintf('%s: %s', $answer->getReceivedAt()->format('d/m/Y H:i'), $answer->getRaw());
                        },
                        'multiple'      => false,
                        'expanded'      => false,
                        'label'         => 'manage_volunteers.anonymize.choose_answer',
                    ])
                    ->add('delete', SubmitType::class, [
                        'label' => 'base.button.delete',
                        'attr'  => [
                            'class' => 'btn-danger',
                        ],
                    ])
                    ->getForm()
                    ->handleRequest($request);
    }
}
