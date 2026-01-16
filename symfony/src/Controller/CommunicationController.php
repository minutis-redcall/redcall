<?php

namespace App\Controller;

use App\Base\BaseController;
use App\Communication\Processor\ProcessorInterface;
use App\Entity\Campaign;
use App\Entity\Communication;
use App\Entity\Message;
use App\Enum\Group;
use App\Enum\Type;
use App\Form\Flow\CallTriggerFlow;
use App\Form\Flow\EmailTriggerFlow;
use App\Form\Flow\SmsTriggerFlow;
use App\Form\Model\BaseTrigger;
use App\Form\Model\Campaign as CampaignModel;
use App\Form\Model\SmsTrigger;
use App\Form\Type\CampaignType;
use App\Manager\AnswerManager;
use App\Manager\BadgeManager;
use App\Manager\CampaignManager;
use App\Manager\CommunicationManager;
use App\Manager\ExpirableManager;
use App\Manager\LanguageConfigManager;
use App\Manager\MediaManager;
use App\Manager\MessageManager;
use App\Manager\PlatformConfigManager;
use App\Manager\StructureManager;
use App\Provider\Minutis\MinutisProvider;
use App\Services\MessageFormatter;
use App\Tools\GSM;
use Bundles\TwilioBundle\Manager\TwilioCallManager;
use Bundles\TwilioBundle\Manager\TwilioMessageManager;
use Bundles\TwilioBundle\Manager\TwilioStatusManager;
use Craue\FormFlowBundle\Form\FormFlow;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route(name="communication_")
 */
class CommunicationController extends BaseController
{
    /**
     * @var CampaignManager
     */
    private $campaignManager;

    /**
     * @var CommunicationManager
     */
    private $communicationManager;

    /**
     * Message formatter, used for preview
     *
     * @var MessageFormatter
     */
    private $formatter;

    /**
     * @var BadgeManager
     */
    private $badgeManager;

    /**
     * @var MessageManager
     */
    private $messageManager;

    /**
     * @var AnswerManager
     */
    private $answerManager;

    /**
     * @var MediaManager
     */
    private $mediaManager;

    /**
     * @var StructureManager
     */
    private $structureManager;

    /**
     * @var ExpirableManager
     */
    private $expirableManager;

    /**
     * @var LanguageConfigManager
     */
    private $languageManager;

    /**
     * @var PlatformConfigManager
     */
    private $platformManager;

    /**
     * @var \App\Repository\VolunteerGroupRepository
     */
    private $volunteerGroupRepository;

    public function __construct(CampaignManager $campaignManager,
        CommunicationManager $communicationManager,
        MessageFormatter $formatter,
        BadgeManager $badgeManager,
        MessageManager $messageManager,
        AnswerManager $answerManager,
        MediaManager $mediaManager,
        StructureManager $structureManager,
        ExpirableManager $expirableManager,
        LanguageConfigManager $languageManager,
        PlatformConfigManager $platformManager,
        \App\Repository\VolunteerGroupRepository $volunteerGroupRepository)
    {
        $this->campaignManager          = $campaignManager;
        $this->communicationManager     = $communicationManager;
        $this->formatter                = $formatter;
        $this->badgeManager             = $badgeManager;
        $this->messageManager           = $messageManager;
        $this->answerManager            = $answerManager;
        $this->mediaManager             = $mediaManager;
        $this->structureManager         = $structureManager;
        $this->expirableManager         = $expirableManager;
        $this->languageManager          = $languageManager;
        $this->platformManager          = $platformManager;
        $this->volunteerGroupRepository = $volunteerGroupRepository;
    }

    /**
     * @Route(path="campaign/{id}", name="index", requirements={"id" = "\d+"})
     * @IsGranted("CAMPAIGN_ACCESS", subject="campaign")
     */
    public function indexAction(Campaign $campaign, MinutisProvider $minutis)
    {
        $this->get('session')->save();

        return $this->render('status_communication/index.html.twig', [
            'campaign'           => $campaign,
            'skills'             => $this->badgeManager->getPublicBadges($this->getPlatform()),
            'progress'           => $campaign->getCampaignProgression(),
            'hash'               => $this->campaignManager->getHash($campaign->getId()),
            'campaignStructures' => $this->structureManager->getCampaignStructures($this->getPlatform(), $campaign),
            'operationUrl'       => $campaign->getOperationUrl($minutis),
            'volunteerGroups'    => $this->volunteerGroupRepository->getVolunteerGroups($campaign->getId()),
            'groupColors'        => Group::getGroups(),
        ]);
    }

    /**
     * @Route(path="campaign/goto/{id}", name="goto", requirements={"id" = "\d+"})
     * @IsGranted("COMMUNICATION", subject="communication")
     */
    public function gotoAction(Communication $communication)
    {
        return $this->redirectToRoute('communication_index', [
            'id' => $communication->getCampaign()->getId(),
        ]);
    }

    /**
     * @Route(path="campaign/{id}/short-polling", name="short_polling", requirements={"id" = "\d+"})
     * @IsGranted("CAMPAIGN_ACCESS", subject="campaign")
     */
    public function shortPolling(Campaign $campaign, TranslatorInterface $translator)
    {
        $this->get('session')->save();

        return new JsonResponse(
            array_merge($campaign->getCampaignStatus($translator), [
                'volunteerGroups' => $this->volunteerGroupRepository->getVolunteerGroups($campaign->getId()),
            ])
        );
    }

    /**
     * @Route(path="campaign/{id}/long-polling", name="long_polling", requirements={"id" = "\d+"})
     * @IsGranted("CAMPAIGN_ACCESS", subject="campaign")
     */
    public function longPolling(Campaign $campaign, Request $request, TranslatorInterface $translator)
    {
        // Always close the session to prevent against session locks
        $this->get('session')->save();

        $secs = 0;
        while ($secs < 10) {
            $hash = $this->campaignManager->getHash($campaign->getId());

            if ($request->get('hash') !== $hash) {
                $this->campaignManager->refresh($campaign);

                return new JsonResponse(
                    array_merge($campaign->getCampaignStatus($translator), [
                        'hash'            => $hash,
                        'volunteerGroups' => $this->volunteerGroupRepository->getVolunteerGroups($campaign->getId()),
                    ])
                );
            }

            sleep(1);
            $secs++;
        }

        return new Response();
    }

    /**
     * @Route(
     *     name="add",
     *     path="campaign/{id}/add-communication/{type}",
     *     requirements={"id" = "\d+"}
     * )
     * @IsGranted("CAMPAIGN_ACCESS", subject="campaign")
     */
    public function addCommunicationAction(Request $request, Campaign $campaign, Type $type)
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user->getVolunteer() || !$user->getStructures()->count()) {
            return $this->redirectToRoute('home');
        }

        $selection = json_decode($request->request->get('volunteers', '[]'), true);

        $key = $this->expirableManager->set([
            'volunteers' => $selection,
        ]);

        return $this->redirectToRoute('communication_new', [
            'id'   => $campaign->getId(),
            'key'  => $key,
            'type' => $type,
        ]);
    }

    /**
     * @Route(
     *     name="new",
     *     path="campaign/{id}/new-communication/{type}/{key}",
     *     defaults={"key" = null},
     *     requirements={"id" = "\d+"}
     * )
     * @IsGranted("CAMPAIGN_ACCESS", subject="campaign")
     */
    public function newCommunicationAction(
        Campaign $campaign,
        Type $type,
        ?string $key,
        SmsTriggerFlow $smsTriggerFlow,
        CallTriggerFlow $callTriggerFlow,
        EmailTriggerFlow $emailTriggerFlow)
    {
        $flows = [
            SmsTriggerFlow::class   => $smsTriggerFlow,
            CallTriggerFlow::class  => $callTriggerFlow,
            EmailTriggerFlow::class => $emailTriggerFlow,
        ];

        /** @var FormFlow $flow */
        $flow = $flows[$type->getFormFlow()];

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user->getVolunteer() || !$user->getStructures()->count()) {
            return $this->redirectToRoute('home');
        }

        /**
         * @var BaseTrigger
         */
        $trigger = $type->getFormData();
        $trigger->setOperation($campaign->hasOperation());
        $trigger->setAudience([
            'preselection_key' => $key,
        ]);
        $trigger->setLanguage(
            $this->platformManager->getPlaform($this->getPlatform())->getDefaultLanguage()->getLocale()
        );
        $trigger->setAnswers([]);

        $flow->bind($trigger);
        $form = $flow->createForm();

        if ($flow->isValid($form)) {
            $flow->saveCurrentStepData($form);

            if ($flow->nextStep()) {
                $form = $flow->createForm();
            } else {
                $communication = $this->communicationManager->createCommunicationEntityFromTrigger($trigger);
                if (0 === $communication->getMessageCount()) {
                    throw new NotFoundHttpException('New communication has no message');
                }

                $communication = $this->communicationManager->createNewCommunication($campaign, $trigger, $communication);

                $this->communicationManager->launchNewCommunication($campaign, $communication);

                return $this->redirect($this->generateUrl('communication_index', [
                        'id' => $campaign->getId(),
                    ]).'#tab-'.$communication->getId());
            }
        }

        return $this->render('new_communication/add.html.twig', [
            'campaign' => $campaign,
            'form'     => $form->createView(),
            'flow'     => $flow,
            'type'     => $type,
            'key'      => $key,
        ]);
    }

    /**
     * @Route(path="campaign/preview/{type}", name="preview")
     */
    public function previewCommunicationAction(Request $request, Type $type, \HTMLPurifier $purifier)
    {
        /** @var \App\Entity\User $user */
        $user    = $this->getUser();
        $trigger = $this->getCommunicationFromRequest($request, $type);

        if (!strip_tags($trigger->getMessage())
            || !$user->getVolunteer() || !$user->getVolunteer()->getPhone()) {
            return new JsonResponse([
                'success' => false,
            ]);
        }

        $communicationEntity = $this->communicationManager->createCommunicationEntityFromTrigger($trigger);

        $message = new Message();
        $message->setCommunication($communicationEntity);
        $message->setPrefix('X');
        $message->setCode('xxxxxxxx');
        $message->setVolunteer(
            $user->getVolunteer()
        );

        $content   = $this->formatter->formatMessageContent($message);
        $parts     = GSM::getSMSParts($content);
        $estimated = $communicationEntity->getEstimatedCost($content);

        return new JsonResponse([
            'success' => true,
            'type'    => $communicationEntity->getType(),
            'message' => $communicationEntity->isEmail() ? $purifier->purify($content) : htmlentities($content),
            'cost'    => count($parts),
            'price'   => round($estimated, 2),
            'length'  => array_sum(array_map('mb_strlen', $parts)),
        ]);
    }

    /**
     * @Route(path="campaign/play", name="play")
     */
    public function playCommunication(Request $request)
    {
        /** @var \App\Entity\User $user */
        $user    = $this->getUser();
        $trigger = $this->getCommunicationFromRequest($request, Type::CALL());

        if (!$trigger->getMessage()) {
            return new JsonResponse(['success' => false]);
        }

        $communicationEntity = $this->communicationManager->createCommunicationEntityFromTrigger($trigger);

        $message = new Message();
        $message->setCommunication($communicationEntity);
        $message->setPrefix('X');
        $message->setCode('xxxxxxxx');

        $message->setVolunteer(
            $user->getVolunteer()
        );

        $media = $this->mediaManager->createMp3(
            $this->languageManager->getLanguageConfigForCommunication($communicationEntity)->getTextToSpeech(),
            $this->formatter->formatMessageContent($message)
        );

        return new JsonResponse([
            'success' => true,
            'player'  => $this->renderView('new_communication/player.html.twig', [
                'media' => $media,
            ]),
        ]);
    }

    /**
     * @Route(
     *     name="answers",
     *     path="campaign/answers"
     * )
     */
    public function answersAction(Request $request)
    {
        $messageId = $request->query->get('messageId');
        if (!$messageId) {
            throw $this->createNotFoundException();
        }

        $message = $this->messageManager->find($messageId);
        if (!$message) {
            throw $this->createNotFoundException();
        }

        if (!$this->isGranted('CAMPAIGN_ACCESS', $message->getCommunication()->getCampaign())) {
            throw $this->createAccessDeniedException();
        }

        $form = $this
            ->createFormBuilder()
            ->add('content', TextareaType::class, [
                'label'       => 'campaign_status.answers.new',
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => 300]),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'base.button.submit',
            ])
            ->getForm()
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->answerManager->sendSms($message, $form->get('content')->getData());
        }

        return $this->render('status_communication/answers.html.twig', [
            'message' => $message,
            'form'    => $form->createView(),
        ]);
    }

    /**
     * @Route(
     *     name="change_answer",
     *     path="campaign/answer/{csrf}/{id}",
     *     requirements={"id" = "\d+"}
     * )
     */
    public function changeAnswerAction(Request $request, Message $message, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('communication', $csrf);

        if (!$this->isGranted('CAMPAIGN_ACCESS', $message->getCommunication()->getCampaign())) {
            throw $this->createAccessDeniedException();
        }

        $choiceEntity = null;
        $choiceId     = $request->request->get('choiceId');
        foreach ($message->getCommunication()->getChoices() as $choice) {
            if ($choice->getId() == $choiceId) {
                $choiceEntity = $choice;
            }
        }
        if (!$choiceEntity) {
            throw $this->createNotFoundException();
        }

        $this->messageManager->toggleAnswer($message, $choiceEntity);

        return new Response();
    }

    /**
     * @Route(path="campaign/{campaignId}/rename-communication/{communicationId}", name="rename")
     * @Entity("campaign", expr="repository.find(campaignId)")
     * @Entity("communicationEntity", expr="repository.find(communicationId)")
     * @IsGranted("CAMPAIGN_ACCESS", subject="campaign")
     */
    public function rename(Request $request,
        Campaign $campaign,
        Communication $communicationEntity,
        ValidatorInterface $validator) : Response
    {
        $this->validateCsrfOrThrowNotFoundException('communication', $request->request->get('csrf'));

        $communication = new SmsTrigger();
        $communication->setLabel($request->request->get('new_name'));
        $errors = $validator->validate($communication, null, ['label_edition']);
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
        } else {
            $this->communicationManager->changeName($communicationEntity, $communication->getLabel());
        }

        return $this->redirect($this->generateUrl('communication_index', [
            'id' => $campaign->getId(),
        ]));
    }

    /**
     * @Route("campaign/{campaign}/communication/{communication}/relaunch", name="relaunch")
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function relaunchCommunication(Campaign $campaign,
        Communication $communication,
        ProcessorInterface $processor)
    {
        foreach ($communication->getMessages() as $message) {
            $message->setError(false);
            $message->setSent(false);
            $this->messageManager->save($message);
        }

        $processor->process($communication);

        return $this->redirectToRoute('communication_index', ['id' => $campaign->getId()]);
    }

    /**
     * @Route(path="campaign/{campaignId}/provider-information/{messageId}", name="provider_information")
     * @Entity("campaign", expr="repository.find(campaignId)")
     * @Entity("message", expr="repository.find(messageId)")
     * @IsGranted("CAMPAIGN_ACCESS", subject="campaign")
     */
    public function getProviderInformation(Campaign $campaign,
        Message $message,
        TranslatorInterface $translator,
        TwilioStatusManager $statusManager,
        TwilioMessageManager $messageManager,
        TwilioCallManager $callManager)
    {
        if (!$message->getCommunication()->getCampaign()->equals($campaign)) {
            throw $this->createNotFoundException();
        }

        return $this->json([
            'title' => $translator->trans('campaign_status.provider.message_id', [
                '%id%' => $message->getMessageId(),
            ]),
            'body'  => $this->renderView('status_communication/provider.html.twig', [
                'context'  => $messageManager->getBySid($message->getMessageId()) ?? $callManager->getBySid($message->getMessageId()),
                'statuses' => $statusManager->getStatuses($message->getMessageId()),
            ]),
        ]);
    }

    private function getCommunicationFromRequest(Request $request, Type $type) : BaseTrigger
    {
        if ($request->request->get('campaign')) {
            // New campaign form
            $campaign = new CampaignModel($type->getFormData());

            $this
                ->createForm(CampaignType::class, $campaign, [
                    'type' => $type,
                ])
                ->handleRequest($request);

            $trigger = $campaign->trigger;
        } else {
            // Add communication form
            $trigger = $type->getFormData();
            $this
                ->createForm($type->getFormType(), $trigger)
                ->handleRequest($request);
        }

        return $trigger;
    }
}
