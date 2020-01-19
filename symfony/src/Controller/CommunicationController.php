<?php

namespace App\Controller;

use App\Base\BaseController;
use App\Entity\Campaign;
use App\Entity\Communication;
use App\Entity\Message;
use App\Form\Model\Communication as CommunicationModel;
use App\Form\Type\CampaignType;
use App\Form\Type\CommunicationType;
use App\Manager\AnswerManager;
use App\Manager\CampaignManager;
use App\Manager\CommunicationManager;
use App\Manager\MessageManager;
use App\Manager\TagManager;
use App\Manager\VolunteerManager;
use App\Services\MessageFormatter;
use App\Tools\GSM;
use App\Tools\Random;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
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
     * CommunicationController constructor.
     *
     * @param CampaignManager      $campaignManager
     * @param CommunicationManager $communicationManager
     * @param TagManager           $tagManager
     * @param VolunteerManager     $volunteerManager
     * @param MessageManager       $messageManager
     * @param AnswerManager        $answerManager
     */
    public function __construct(CampaignManager $campaignManager,
        CommunicationManager $communicationManager,
        MessageFormatter $formatter,
        TagManager $tagManager,
        VolunteerManager $volunteerManager,
        MessageManager $messageManager,
        AnswerManager $answerManager)
    {
        $this->campaignManager      = $campaignManager;
        $this->communicationManager = $communicationManager;
        $this->formatter            = $formatter;
        $this->tagManager           = $tagManager;
        $this->volunteerManager     = $volunteerManager;
        $this->messageManager       = $messageManager;
        $this->answerManager        = $answerManager;
    }

    /**
     * @Route(path="campaign/{campaignId}", name="index", requirements={"campaignId" = "\d+"})
     *
     * @param int $campaignId
     *
     * @return Response
     */
    public function indexAction(int $campaignId)
    {
        $this->get('session')->save();

        $campaign = $this->getCampaignOrThrowNotFoundException($campaignId);

        return $this->render('status_communication/index.html.twig', [
            'campaign'   => $campaign,
            'skills'     => $this->tagManager->findAll(),
            'statusHash' => $this->getStatusHash($campaign),
            'progress'   => $campaign->getCampaignProgression(),
        ]);
    }

    /**
     * @Route(path="campaign/{campaignId}/poll", name="poll", requirements={"campaignId" = "\d+"})
     *
     * @param int $campaignId
     *
     * @return Response
     */
    public function pollAction(int $campaignId)
    {
        $this->get('session')->save();

        $campaign = $this->getCampaignOrThrowNotFoundException($campaignId);

        return new JsonResponse(
            $campaign->getCampaignStatus()
        );
    }

    /**
     * @Route(
     *     name="add",
     *     path="campaign/{campaignId}/add-communication",
     *     requirements={"campaignId" = "\d+"}
     * )
     * @Method("POST")
     *
     * @param Request $request
     * @param int     $campaignId
     *
     * @return Response
     */
    public function addCommunicationAction(Request $request, int $campaignId)
    {
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
        if (!isset($selections[$campaignId])) {
            $selections[$campaignId] = [];
        }
        $key                           = Random::generate(8);
        $selections[$campaignId][$key] = $selection;
        if ($count = count($selections[$campaignId]) > 100) {
            $selections[$campaignId] = array_slice($selections[$campaignId], $count - 100);
        }
        $this->get('session')->set('add-communication', $selections);

        return $this->redirectToRoute('communication_new', [
            'campaignId' => $campaignId,
            'key'        => $key,
        ]);
    }

    /**
     * @Route(
     *     name="new",
     *     path="campaign/{campaignId}/new-communication/{key}",
     *     defaults={"key" = null},
     *     requirements={"campaignId" = "\d+"}
     * )
     *
     * @param Request $request
     * @param int     $campaignId
     * @param string  $key
     *
     * @return Response
     */
    public function newCommunicationAction(Request $request, int $campaignId, ?string $key)
    {
        // If volunteers selection have been made on the communication page,
        // restore it from the session.
        $volunteers = [];
        if (!is_null($key)) {
            $selection = $this->get('session')->get('add-communication', [])[$campaignId][$key] ?? [];
            foreach ($selection as $volunteerId) {
                $volunteer = $this->volunteerManager->find($volunteerId);
                if ($volunteer) {
                    $volunteers[] = $volunteer;
                }
            }
        }

        /**
         * @var \App\Form\Model\Communication
         */
        $communication             = new \App\Form\Model\Communication();
        $communication->volunteers = $volunteers;
        $communication->answers    = [];
        $campaign                  = $this->getCampaignOrThrowNotFoundException($campaignId);

        $form = $this
            ->createForm(CommunicationType::class, $communication)
            ->handleRequest($request);

        // Creating the new communication is form has been submitted
        if ($form->isSubmitted() && $form->isValid()) {
            $this->communicationManager->launchNewCommunication($campaign, $communication);

            return $this->redirect($this->generateUrl('communication_index', [
                'campaignId' => $campaign->getId(),
            ]));
        }

        return $this->render('new_communication/page.html.twig', [
            'campaign'       => $campaign,
            'volunteers'     => $volunteers,
            'form'           => $form->createView(),
            'taken_prefixes' => $this->communicationManager->getTakenPrefixes(),
        ]);
    }

    /**
     * @Route(path="campaign/preview", name="preview")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function previewCommunicationAction(Request $request)
    {
        if ($request->request->get('campaign')) {
            // New campaign form
            $campaignModel = new \App\Form\Model\Campaign();
            $this
                ->createForm(CampaignType::class, $campaignModel)
                ->handleRequest($request);
            $communicationModel = $campaignModel->communication;
        } else {
            // Add communication form
            $communicationModel = new \App\Form\Model\Communication();
            $this
                ->createForm(CommunicationType::class, $communicationModel)
                ->handleRequest($request);
        }

        if (!$communicationModel->message) {
            return new JsonResponse(['success' => false]);
        }

        $communicationEntity = $this->communicationManager->createCommunication($communicationModel);

        $message = new Message();
        $message->setCommunication($communicationEntity);
        $message->setWebCode('xxxxxxxx');

        $content = $this->formatter->formatMessageContent($message);
        $parts   = GSM::getSMSParts($content);

        return new JsonResponse([
            'success' => true,
            'message' => $content,
            'cost'    => count($parts),
            'length'  => array_sum(array_map('mb_strlen', $parts)),
        ]);
    }

    /**
     * @Route(
     *     name="answers",
     *     path="campaign/answers"
     * )
     *
     * @param Request $request
     *
     * @return Response
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

        return $this->render('status_communication/answers.html.twig', [
            'message' => $message,
        ]);
    }

    /**
     * @Route(
     *     name="change_answer",
     *     path="campaign/answer/{csrf}/{messageId}",
     *     requirements={"messageId" = "\d+"}
     * )
     *
     * @param Request $request
     * @param int     $messageId
     * @param string  $csrf
     *
     * @return Response
     */
    public function changeAnswerAction(Request $request, int $messageId, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('communication', $csrf);

        /* @var Message $message */
        $message = $this->messageManager->find($messageId);
        if (!$message) {
            throw $this->createNotFoundException();
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
     *
     * @param Request $request
     * @param int     $campaignId
     * @param int     $communicationId
     *
     * @return Response
     */
    public function rename(Request $request, int $campaignId, int $communicationId): Response
    {
        $this->validateCsrfOrThrowNotFoundException('communication', $request->request->get('csrf'));
        $communicationEntity = $this->getCommunicationOrThrowNotFoundExcpetion($communicationId);

        $communication        = new CommunicationModel();
        $communication->label = $request->request->get('new_name');
        $errors               = $this->get('validator')->validate($communication, null, ['label_edition']);
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
        } else {
            $this->communicationManager->changeName($communicationEntity, $communication->label);
        }

        return $this->redirect($this->generateUrl('communication_index', [
            'campaignId' => $campaignId,
        ]));
    }

    /**
     * @param int $campaignid
     *
     * @return Campaign
     *
     * @throws NotFoundHttpException
     */
    private function getCampaignOrThrowNotFoundException(int $campaignId): Campaign
    {
        $campaign = $this->campaignManager->find($campaignId);

        if (is_null($campaign)) {
            throw $this->createNotFoundException();
        }

        return $campaign;
    }

    /**
     * @param int $communicationId
     *
     * @return Communication
     *
     * @throws NotFoundHttpException
     */
    private function getCommunicationOrThrowNotFoundExcpetion(int $communicationId): Communication
    {
        $communication = $this->communicationManager->find($communicationId);

        if (is_null($communication)) {
            throw $this->createNotFoundException();
        }

        return $communication;
    }

    /**
     * Returns the campaign's status hash, a hash used by SSE in order
     * to know if campaign status is up to date or needs to be refreshed.
     *
     * Notes:
     * - status hash should match [0-9-]*
     * - no cache should be involved (take care of doctrine)
     *
     * @param Campaign $campaign
     *
     * @return string
     */
    private function getStatusHash(Campaign $campaign): string
    {
        return sprintf(
            '%s-%s',

            // New answer arrived
            $this->answerManager->getLastCampaignUpdateTimestamp($campaign),

            // Number of messages sent changed
            $this->messageManager->getNumberOfSentMessages($campaign)
        );
    }
}
