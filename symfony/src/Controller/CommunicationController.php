<?php

namespace App\Controller;

use App\Base\BaseController;
use App\Communication\Processor\ProcessorInterface;
use App\Entity\Campaign;
use App\Entity\Communication;
use App\Entity\Message;
use App\Enum\Type;
use App\Form\Model\BaseTrigger;
use App\Form\Model\Campaign as CampaignModel;
use App\Form\Model\SmsTrigger;
use App\Form\Type\CampaignType;
use App\Manager\AnswerManager;
use App\Manager\CampaignManager;
use App\Manager\CommunicationManager;
use App\Manager\MediaManager;
use App\Manager\MessageManager;
use App\Manager\StructureManager;
use App\Manager\TagManager;
use App\Manager\UserManager;
use App\Manager\VolunteerManager;
use App\Services\MessageFormatter;
use App\Tools\GSM;
use App\Tools\Random;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
     * Message formatter, used for previsualization
     *
     * @var MessageFormatter
     */
    private $formatter;

    /**
     * @var TagManager
     */
    private $tagManager;

    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @var MessageManager
     */
    private $messageManager;

    /**
     * @var AnswerManager
     */
    private $answerManager;

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var MediaManager
     */
    private $mediaManager;

    /**
     * @var StructureManager
     */
    private $structureManager;

    public function __construct(CampaignManager $campaignManager,
        CommunicationManager $communicationManager,
        MessageFormatter $formatter,
        TagManager $tagManager,
        VolunteerManager $volunteerManager,
        MessageManager $messageManager,
        AnswerManager $answerManager,
        UserManager $userManager,
        MediaManager $mediaManager,
        StructureManager $structureManager)
    {
        $this->campaignManager      = $campaignManager;
        $this->communicationManager = $communicationManager;
        $this->formatter            = $formatter;
        $this->tagManager           = $tagManager;
        $this->volunteerManager     = $volunteerManager;
        $this->messageManager       = $messageManager;
        $this->answerManager        = $answerManager;
        $this->userManager          = $userManager;
        $this->mediaManager         = $mediaManager;
        $this->structureManager     = $structureManager;
    }

    /**
     * @Route(path="campaign/{id}", name="index", requirements={"id" = "\d+"})
     * @IsGranted("CAMPAIGN_ACCESS", subject="campaign")
     */
    public function indexAction(Campaign $campaign)
    {
        $this->get('session')->save();

        return $this->render('status_communication/index.html.twig', [
            'campaign'           => $campaign,
            'skills'             => $this->tagManager->findAll(),
            'progress'           => $campaign->getCampaignProgression(),
            'hash'               => $this->campaignManager->getHash($campaign->getId()),
            'campaignStructures' => $this->structureManager->getCampaignStructures($campaign),
        ]);
    }

    /**
     * @Route(path="campaign/{id}/short-polling", name="short_polling", requirements={"id" = "\d+"})
     * @IsGranted("CAMPAIGN_ACCESS", subject="campaign")
     */
    public function shortPolling(Campaign $campaign)
    {
        $this->get('session')->save();

        return new JsonResponse(
            $campaign->getCampaignStatus()
        );
    }

    /**
     * @Route(path="campaign/{id}/long-polling", name="long_polling", requirements={"id" = "\d+", "olHash" =
     *                                           "[0-9a-f]{40}"})
     * @IsGranted("CAMPAIGN_ACCESS", subject="campaign")
     */
    public function longPolling(Campaign $campaign, Request $request)
    {
        // Always close the session to prevent against session locks
        $this->get('session')->save();

        $secs = 0;
        while ($secs < 10) {
            $hash = $this->campaignManager->getHash($campaign->getId());

            if ($request->get('hash') !== $hash) {
                $this->campaignManager->refresh($campaign);

                return new JsonResponse(
                    array_merge($campaign->getCampaignStatus(), [
                        'hash' => $hash,
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
     * @Method("POST")
     */
    public function addCommunicationAction(Request $request, Campaign $campaign, Type $type)
    {
        $user = $this->getUser();

        if (!$user->getVolunteer() || !$user->getStructures()->count()) {
            return $this->redirectToRoute('home');
        }

        $selection = json_decode($request->request->get('volunteers', '[]'), true);

        foreach ($selection as $volunteerId) {
            $volunteer = $this->volunteerManager->find($volunteerId);
            if (!$volunteer) {
                throw $this->createNotFoundException();
            }
        }

        // We should access the form using GET method, thus we need to store
        // the volunteer selection in the session. But in the meantime, we
        // should allow the dispatcher to create several new communications
        // on separate tabs.
        $selections = $this->get('session')->get('add-communication', []);
        if (!isset($selections[$campaign->getId()])) {
            $selections[$campaign->getId()] = [];
        }
        $key                                  = Random::generate(8);
        $selections[$campaign->getId()][$key] = $selection;
        if ($count = count($selections[$campaign->getId()]) > 100) {
            $selections[$campaign->getId()] = array_slice($selections[$campaign->getId()], $count - 100);
        }
        $this->get('session')->set('add-communication', $selections);

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
    public function newCommunicationAction(Request $request, Campaign $campaign, Type $type, ?string $key)
    {
        $user = $this->getUser();

        if (!$user->getVolunteer() || !$user->getStructures()->count()) {
            return $this->redirectToRoute('home');
        }

        // If volunteers selection have been made on the communication page,
        // restore it from the session.
        $volunteers = [];
        if (!is_null($key)) {
            $selection = $this->get('session')->get('add-communication', [])[$campaign->getId()][$key] ?? [];

            foreach ($selection as $volunteerId) {
                $volunteer = $this->volunteerManager->find($volunteerId);
                if ($volunteer) {
                    $volunteers[] = $volunteer->getNivol();
                }
            }
        }

        /**
         * @var BaseTrigger
         */
        $communication = $type->getFormData();
        $communication->setAudience($volunteers);
        $communication->setAnswers([]);

        $form = $this
            ->createForm($type->getFormType(), $communication)
            ->handleRequest($request);

        if ($form->get('test')->isClicked()) {
            $communication->setAudience([
                $this->getUser()->getNivol(),
            ]);
        }

        // Creating the new communication is form has been submitted
        if ($form->isSubmitted() && $form->isValid()) {
            $this->communicationManager->launchNewCommunication($campaign, $communication);

            return $this->redirect($this->generateUrl('communication_index', [
                'id' => $campaign->getId(),
            ]));
        }

        return $this->render('new_communication/page.html.twig', [
            'campaign'   => $campaign,
            'volunteers' => $volunteers,
            'form'       => $form->createView(),
            'type'       => $type,
        ]);
    }

    /**
     * @Route(path="campaign/preview/{type}", name="preview")
     */
    public function previewCommunicationAction(Request $request, Type $type, \HTMLPurifier $purifier)
    {
        $trigger = $this->getCommunicationFromRequest($request, $type);

        if (!strip_tags($trigger->getMessage())
            || !$this->getUser()->getVolunteer() || !$this->getUser()->getVolunteer()->getPhone()) {
            return new JsonResponse([
                'success' => false,
            ]);
        }

        $communicationEntity = $this->communicationManager->createCommunication($trigger);

        $message = new Message();
        $message->setCommunication($communicationEntity);
        $message->setPrefix('X');
        $message->setCode('xxxxxxxx');
        $message->setVolunteer(
            $this->getUser()->getVolunteer()
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
        $trigger = $this->getCommunicationFromRequest($request, Type::CALL());

        if (!$trigger->getMessage()) {
            return new JsonResponse(['success' => false]);
        }

        $communicationEntity = $this->communicationManager->createCommunication($trigger);

        $message = new Message();
        $message->setCommunication($communicationEntity);
        $message->setPrefix('X');
        $message->setCode('xxxxxxxx');

        $media = $this->mediaManager->createMp3(
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

        return $this->render('status_communication/answers.html.twig', [
            'message' => $message,
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
    public function rename(Request $request, Campaign $campaign, Communication $communicationEntity) : Response
    {
        $this->validateCsrfOrThrowNotFoundException('communication', $request->request->get('csrf'));

        $communication = new SmsTrigger();
        $communication->setLabel($request->request->get('new_name'));
        $errors = $this->get('validator')->validate($communication, null, ['label_edition']);
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
            $message->setSent(false);
            $this->messageManager->save($message);
        }

        $processor->process($communication);

        return $this->redirectToRoute('communication_index', ['id' => $campaign->getId()]);
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
