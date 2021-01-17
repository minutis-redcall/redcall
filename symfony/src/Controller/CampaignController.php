<?php

namespace App\Controller;

use App\Base\BaseController;
use App\Entity\Campaign;
use App\Enum\Type;
use App\Form\Model\Campaign as CampaignModel;
use App\Form\Model\SmsTrigger;
use App\Form\Type\CampaignType;
use App\Manager\CampaignManager;
use App\Manager\CommunicationManager;
use App\Manager\UserManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CampaignController extends BaseController
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
     * @var UserManager
     */
    private $userManager;

    public function __construct(CampaignManager $campaignManager,
        CommunicationManager $communicationManager,
        UserManager $userManager)
    {
        $this->campaignManager      = $campaignManager;
        $this->communicationManager = $communicationManager;
        $this->userManager          = $userManager;
    }

    /**
     * @Route(path="campaign/list", name="list_campaigns")
     */
    public function listCampaigns()
    {
        return $this->render('campaign/list.html.twig');
    }

    public function renderCampaignsTable() : Response
    {
        $byMyCrew      = $this->campaignManager->getCampaignsOpenedByMeOrMyCrew($this->getUser());
        $byMyTeammates = $this->campaignManager->getCampaignImpactingMyVolunteers($this->getUser());
        $finished      = $this->campaignManager->getInactiveCampaignsForUserQueryBuilder($this->getUser());

        return $this->render('campaign/table.html.twig', [
            'data' => [
                'my_structures' => [
                    'orderBy' => $this->orderBy($byMyCrew, Campaign::class, 'c.createdAt', 'DESC', 'crew'),
                    'pager'   => $this->getPager($byMyCrew, 'ongoing'),
                ],
                'my_volunteers' => [
                    'orderBy' => $this->orderBy($byMyTeammates, Campaign::class, 'c.createdAt', 'DESC', 'mates'),
                    'pager'   => $this->getPager($byMyTeammates, 'ongoing'),
                ],
                'finished'      => [
                    'orderBy' => $this->orderBy($finished, Campaign::class, 'c.createdAt', 'DESC', 'finished'),
                    'pager'   => $this->getPager($finished, 'finished'),
                ],
            ],
        ]);
    }

    /**
     * @Route(path="campaign/new/{type}", name="create_campaign")
     */
    public function createCampaign(Request $request, Type $type)
    {
        $user = $this->getUser();

        if (!$user->getVolunteer() || !$user->getStructures()->count()) {
            return $this->redirectToRoute('home');
        }

        $campaignModel = new CampaignModel(
            $type->getFormData()
        );

        $form = $this
            ->createForm(CampaignType::class, $campaignModel, [
                'type' => $type,
            ])
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $campaignEntity = $this->campaignManager->launchNewCampaign($campaignModel);

            if (!$campaignEntity) {
                return $this->redirectToRoute('home');
            }

            return $this->redirect($this->generateUrl('communication_index', [
                'id' => $campaignEntity->getId(),
            ]));
        }

        return $this->render('new_communication/new.html.twig', [
            'form' => $form->createView(),
            'type' => $type,
        ]);
    }

    /**
     * @Route(path="campaign/{id}/audience", name="audience_campaign")
     * @IsGranted("CAMPAIGN_ACCESS", subject="campaign")
     */
    public function audience(Campaign $campaign)
    {
        return new JsonResponse(
            $this->campaignManager->getCampaignAudience($campaign)
        );
    }

    /**
     * @Route(path="campaign/{id}/close/{csrf}", name="close_campaign")
     * @IsGranted("CAMPAIGN_OWNER", subject="campaign")
     */
    public function closeCampaign(Campaign $campaign, string $csrf) : Response
    {
        $this->validateCsrfOrThrowNotFoundException('campaign', $csrf);

        // Close the campaign
        if ($campaign->isActive()) {
            $this->campaignManager->closeCampaign($campaign);
        }

        return $this->redirect($this->generateUrl('communication_index', [
            'id' => $campaign->getId(),
        ]));
    }

    /**
     * @Route(path="campaign/{id}/open/{csrf}", name="open_campaign")
     * @IsGranted("CAMPAIGN_ACCESS", subject="campaign")
     */
    public function openCampaign(Campaign $campaign, string $csrf) : Response
    {
        $this->validateCsrfOrThrowNotFoundException('campaign', $csrf);

        if (!$campaign->isActive()) {
            if (!$this->campaignManager->canReopenCampaign($campaign)) {
                $this->danger('campaign.cannot_reopen');
            } else {
                $this->campaignManager->openCampaign($campaign);
            }
        }

        return $this->redirect($this->generateUrl('communication_index', [
            'id' => $campaign->getId(),
        ]));
    }

    /**
     * @Route(path="campaign/{id}/change-color/{color}/{csrf}", name="color_campaign")
     * @IsGranted("CAMPAIGN_OWNER", subject="campaignEntity")
     */
    public function changeColor(Campaign $campaignEntity, string $color, string $csrf) : Response
    {
        $this->validateCsrfOrThrowNotFoundException('campaign', $csrf);

        $campaign       = new CampaignModel(new SmsTrigger());
        $campaign->type = $color;
        $errors         = $this->get('validator')->validate($campaign, null, ['color_edition']);
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
        } else {
            $this->campaignManager->changeColor($campaignEntity, $color);
        }

        return $this->redirect($this->generateUrl('communication_index', [
            'id' => $campaignEntity->getId(),
        ]));
    }

    /**
     * @Route(path="campaign/{id}/rename", name="rename_campaign")
     * @IsGranted("CAMPAIGN_OWNER", subject="campaignEntity")
     */
    public function rename(Request $request, Campaign $campaignEntity) : Response
    {
        $this->validateCsrfOrThrowNotFoundException('campaign', $request->request->get('csrf'));

        $campaign        = new CampaignModel(new SmsTrigger());
        $campaign->label = $request->request->get('new_name');
        $errors          = $this->get('validator')->validate($campaign, null, ['label_edition']);
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
        } else {
            $this->campaignManager->changeName($campaignEntity, $campaign->label);
        }

        return $this->redirect($this->generateUrl('communication_index', [
            'id' => $campaignEntity->getId(),
        ]));
    }

    /**
     * @Route(path="campaign/{id}/notes", name="notes_campaign")
     * @IsGranted("CAMPAIGN_ACCESS", subject="campaignEntity")
     */
    public function notes(Request $request, Campaign $campaignEntity) : Response
    {
        $this->validateCsrfOrThrowNotFoundException('campaign', $request->request->get('csrf'));

        $notes = strip_tags($request->request->get('notes'));

        $this->campaignManager->changeNotes($campaignEntity, $notes);

        return $this->redirect($this->generateUrl('communication_index', [
            'id' => $campaignEntity->getId(),
        ]));
    }
}
