<?php

namespace App\Controller;

use App\Base\BaseController;
use App\Campaign\CampaignManager;
use App\Component\HttpFoundation\EventStreamResponse;
use App\Entity\Campaign;
use App\Entity\Communication;
use App\Form\Model\Communication as CommunicationModel;
use App\Form\Type\CommunicationType;
use App\Repository\AnswerRepository;
use App\Repository\CommunicationRepository;
use App\Repository\MessageRepository;
use App\Repository\TagRepository;
use App\Services\Random;
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
     * @var TagRepository
     */
    private $tagRepository;

    /**
     * @var MessageRepository
     */
    private $messageRepository;

    /**
     * @var AnswerRepository
     */
    private $answerRepository;

    /**
     * @var CommunicationRepository
     */
    private $communicationRepository;

    /**
     * CommunicationController constructor.
     *
     * @param CampaignManager         $campaignManager
     * @param TagRepository           $tagRepository
     * @param MessageRepository       $messageRepository
     * @param AnswerRepository        $answerRepository
     * @param CommunicationRepository $communicationRepository
     */
    public function __construct(CampaignManager $campaignManager,
        TagRepository $tagRepository,
        MessageRepository $messageRepository,
        AnswerRepository $answerRepository,
        CommunicationRepository $communicationRepository)
    {
        $this->campaignManager         = $campaignManager;
        $this->tagRepository           = $tagRepository;
        $this->messageRepository       = $messageRepository;
        $this->answerRepository        = $answerRepository;
        $this->communicationRepository = $communicationRepository;
    }

    /**
     * @Route(path="declenchement/{campaignId}", name="index", requirements={"campaignId" = "\d+"})
     *
     * @param int $campaignId
     *
     * @return Response
     */
    public function indexAction(int $campaignId)
    {
        $campaign = $this->get('doctrine')->getRepository('App:Campaign')->findOneBy([
            'id' => $campaignId,
        ]);

        if (!$campaign) {
            throw $this->createNotFoundException();
        }

        return $this->render('communication.html.twig', [
            'campaign'    => $campaign,
            'skills'      => $this->tagRepository->findAll(),
            'statusHash' => $this->getStatusHash($campaign),
            'progress'    => $campaign->getCampaignProgression(),
        ]);
    }

    /**
     * @Route(path="declenchement/{campaignId}/poll", name="poll", requirements={"campaignId" = "\d+"})
     *
     * @param int $campaignId
     *
     * @return Response
     */
    public function pollAction(int $campaignId)
    {
        /**
         * @var Campaign
         */
        $campaign = $this->get('doctrine')->getRepository('App:Campaign')->findOneBy([
            'id' => $campaignId,
        ]);

        if (!$campaign) {
            throw $this->createNotFoundException();
        }

        return new JsonResponse(
            $campaign->getCampaignStatus()
        );
    }

    /**
     * @Route(path="declenchement/{campaignId}/sse/{status}",
     *        name="sse",
     *        requirements={"campaignId" = "\d+", "status" = "[0-9-]*"}
     * )
     *
     * @param int $campaignId
     *
     * @return Response
     */
    public function sseAction(int $campaignId, ?string $status = null)
    {
        $this->get('session')->save();

        /**
         * @var Campaign
         */
        $campaign = $this->get('doctrine')->getRepository('App:Campaign')->findOneBy([
            'id' => $campaignId,
        ]);

        if (!$campaign) {
            throw $this->createNotFoundException();
        }

        $prevUpdate = $status;
        $response   = new EventStreamResponse(function () use ($campaign, &$prevUpdate) {
            $newUpdate = $this->getStatusHash($campaign);

            if ($newUpdate !== $prevUpdate) {
                $prevUpdate = $newUpdate;
                $campaign   = $this->campaignManager->refresh($campaign);

                return json_encode($campaign->getCampaignStatus());
            }
        });

        return $response;
    }

    /**
     * @Route(
     *     name="add",
     *     path="declenchement/{campaignId}/ajouter-une-communication",
     *     requirements={"campaignId" = "\d+"}
     * )
     * @Method("POST")
     *
     * @param Request $request
     * @param int     $campaignId
     */
    public function addCommunicationAction(Request $request, int $campaignId)
    {
        $selection = json_decode($request->request->get('volunteers', '[]'), true);
        if (!$selection) {
            throw $this->createNotFoundException();
        }

        foreach ($selection as $volunteerId) {
            $volunteer = $this->get('doctrine')->getRepository('App:Volunteer')->find($volunteerId);
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
     *     path="declenchement/{campaignId}/nouvelle-communication/{key}",
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
            $selection = $this->get('session')->get('add-communication', [])[$campaignId][$key] ?? null;
            if (is_null($selection)) {
                return $this->redirectToRoute('communication_index', ['campaignId' => $campaignId]);
            }
            foreach ($selection as $volunteerId) {
                $volunteer = $this->get('doctrine')->getRepository('App:Volunteer')->find($volunteerId);
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
        $communication->answers    = ['', ''];
        $campaign                  = $this->get('doctrine')->getRepository('App:Campaign')->find($campaignId);
        if (!$campaign) {
            throw $this->createNotFoundException();
        }
        $form = $this
            ->createForm(CommunicationType::class, $communication)
            ->handleRequest($request);

        // Creating the new communication is form has been submitted
        if ($form->isSubmitted() && $form->isValid()) {
            $this->campaignManager->createNewCommunication($campaign, $communication);

            return $this->redirect($this->generateUrl('communication_index', [
                'campaignId' => $campaign->getId(),
            ]));
        }

        return $this->render('new_communication.html.twig', [
            'campaign'   => $campaign,
            'volunteers' => $volunteers,
            'form'       => $form->createView(),
        ]);
    }

    /**
     * @Route(
     *     name="answers",
     *     path="declenchement/reponses"
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

        $message = $this->messageRepository->find($messageId);
        if (!$message) {
            throw $this->createNotFoundException();
        }

        return $this->render('answer_communication.html.twig', [
            'message' => $message,
        ]);
    }

    /**
     * @Route(
     *     name="toggle_answer",
     *     path="declenchement/reponses/{messageId}/{csrf}",
     *     requirements={"messageId" = "\d+"}
     * )
     *
     * @param Request $request
     * @param int     $messageId
     * @param string  $csrf
     *
     * @return Response
     */
    public function toggleAnswerAction(Request $request, int $messageId, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('communication', $csrf);

        $message = $this->messageRepository->find($messageId);
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

        $this->messageRepository->toggleAnswer($message, $choiceEntity);

        return new Response();
    }

    /**
     * @Route(path="declenchement/{campaignId}/renommer-communication/{communicationId}", name="rename")
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
            $this->communicationRepository->changeName($communicationEntity, $communication->label);
        }

        return $this->redirect($this->generateUrl('communication_index', [
            'campaignId' => $campaignId,
        ]));
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
        $communication = $this->getManager(Communication::class)->find($communicationId);

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
            $this->answerRepository->getLastCampaignUpdateTimestamp($campaign),

            // Number of messages sent changed
            $this->messageRepository->getNumberOfSentMessages($campaign)
        );
    }
}
