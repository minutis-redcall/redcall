<?php

namespace App\Controller;

use App\Base\BaseController;
use App\Entity\Campaign;
use App\Form\Model\Campaign as CampaignModel;
use App\Form\Type\CampaignType;
use App\Manager\CampaignManager;
use App\Manager\CommunicationManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
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
     * @param CampaignManager      $campaignManager
     * @param CommunicationManager $communicationManager
     */
    public function __construct(CampaignManager $campaignManager, CommunicationManager $communicationManager)
    {
        $this->campaignManager      = $campaignManager;
        $this->communicationManager = $communicationManager;
    }

    /**
     * @Route(path="campaign/list", name="list_campaigns")
     */
    public function listCampaigns()
    {
        return $this->render('campaign/list.html.twig');
    }

    public function renderCampaignsTable(): Response
    {
        $ongoing  = $this->campaignManager->getActiveCampaignsForUserQueryBuilder($this->getUser());
        $finished = $this->campaignManager->getInactiveCampaignsForUserQueryBuilder($this->getUser());

        return $this->render('campaign/table.html.twig', [
            'data' => [
                'ongoing'  => [
                    'orderBy' => $this->orderBy($ongoing, Campaign::class, 'c.createdAt', 'DESC', 'ongoing'),
                    'pager'   => $this->getPager($ongoing, 'ongoing'),
                ],
                'finished' => [
                    'orderBy' => $this->orderBy($finished, Campaign::class, 'c.createdAt', 'DESC', 'finished'),
                    'pager'   => $this->getPager($finished, 'finished'),
                ],
            ],
        ]);
    }

    /**
     * @Route(path="campaign/new", name="create_campaign")
     */
    public function createCampaign(Request $request)
    {
        $campaignModel = new CampaignModel();
        $form          = $this
            ->createForm(CampaignType::class, $campaignModel)
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $campaignEntity = $this->campaignManager->launchNewCampaign($campaignModel);

            return $this->redirect($this->generateUrl('communication_index', [
                'campaignId' => $campaignEntity->getId(),
            ]));
        }

        return $this->render('campaign/new.html.twig', [
            'form'           => $form->createView(),
            'taken_prefixes' => $this->communicationManager->getTakenPrefixes(),
        ]);
    }

    /**
     * @Route(path="campaign/{id}/close/{csrf}", name="close_campaign")
     * @IsGranted("CAMPAIGN", subject="campaign")
     */
    public function closeCampaign(Campaign $campaign, string $csrf): Response
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
     * @IsGranted("CAMPAIGN", subject="campaign")
     */
    public function openCampaign(Campaign $campaign, string $csrf): Response
    {
        $this->validateCsrfOrThrowNotFoundException('campaign', $csrf);

        // Reopen the campaign
        if (!$campaign->isActive()) {
            $this->campaignManager->openCampaign($campaign);
        }

        return $this->redirect($this->generateUrl('communication_index', [
            'id' => $campaign->getId(),
        ]));
    }

    /**
     * @Route(path="campaign/{id}/change-color/{color}/{csrf}", name="color_campaign")
     * @IsGranted("CAMPAIGN", subject="campaignEntity")
     */
    public function changeColor(Campaign $campaignEntity, string $color, string $csrf): Response
    {
        $this->validateCsrfOrThrowNotFoundException('campaign', $csrf);

        $campaign       = new CampaignModel();
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
     * @IsGranted("CAMPAIGN", subject="campaignEntity")
     *
     * @param Request $request
     * @param int     $campaignId
     *
     * @return Response
     */
    public function rename(Request $request, Campaign $campaignEntity): Response
    {
        $this->validateCsrfOrThrowNotFoundException('campaign', $request->request->get('csrf'));

        $campaign        = new CampaignModel();
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
}
