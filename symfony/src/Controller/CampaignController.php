<?php

namespace App\Controller;

use App\Base\BaseController;
use App\Campaign\CampaignManager;
use App\Entity\Campaign;
use App\Form\Model\Campaign as CampaignModel;
use App\Form\Type\CampaignType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CampaignController extends BaseController
{
    /** @var CampaignManager */
    private $campaignManager;

    /**
     * CommunicationController constructor.
     *
     * @param CampaignManager $campaignManager
     */
    public function __construct(CampaignManager $campaignManager)
    {
        $this->campaignManager = $campaignManager;
    }

    /**
     * @Route(path="declenchement/liste", name="list_campaigns")
     * @return Response
     */
    public function listCampaigns()
    {
        return $this->render('campaign/list.html.twig');
    }

    /**
     * @return Response
     */
    public function renderCampaignsTable(Request $request): Response
    {
        $ongoing = $this
            ->get('doctrine')
            ->getManager()
            ->getRepository(Campaign::class)
            ->createQueryBuilder('c')
            ->where('c.active = 1');

        $finished = $this
            ->get('doctrine')
            ->getManager()
            ->getRepository(Campaign::class)
            ->createQueryBuilder('c')
            ->where('c.active = 0');

        return $this->render('campaign/table.html.twig', [
            'data' => [
                'ongoing'  => [
                    'orderBy' => $this->orderBy($ongoing, Campaign::class, 'c.type DESC, c.createdAt', 'DESC', 'ongoing'),
                    'pager'   => $this->getPager($ongoing, 'ongoing'),
                ],
                'finished' => [
                    'orderBy' => $this->orderBy($finished, Campaign::class, 'c.type DESC, c.createdAt', 'DESC', 'finished'),
                    'pager'   => $this->getPager($finished, 'finished'),
                ],
            ],
        ]);
    }

    /**
     * @Route(path="declenchement/nouveau", name="create_campaign")
     *
     * @param Request $request
     *
     * @return string
     * @throws \Exception
     */
    public function createCampaign(Request $request)
    {
        $campaign = new CampaignModel();
        $form     = $this
            ->createForm(CampaignType::class, $campaign)
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $campaignId = $this->campaignManager->launchNewCampaign(
                $campaign->label ?: date('d/m/y H:i'),
                $campaign->type,
                $campaign->communication->volunteers,
                $campaign->communication->message,
                $campaign->communication->answers,
                $campaign->communication->geoLocation,
                $campaign->communication->type,
                $campaign->communication->multipleAnswer,
                $campaign->communication->subject
            );

            return $this->redirect($this->generateUrl('communication_index', [
                'campaignId' => $campaignId,
            ]));
        }

        return $this->render('campaign/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route(path="declenchement/{campaignId}/fermer/{csrf}", name="close_campaign")
     *
     * @param int    $campaignId
     * @param string $csrf
     *
     * @return Response
     */
    public function closeCampaign(int $campaignId, string $csrf): Response
    {
        $this->validateCsrfOrThrowNotFoundException('campaign', $csrf);

        $campaign = $this->getCampaignOrThrowNotFoundExcpetion($campaignId);

        // Close the campaign
        if ($campaign->isActive()) {
            $this->campaignManager->closeCampaign($campaign);
        }

        return $this->redirect($this->generateUrl('communication_index', [
            'campaignId' => $campaign->getId(),
        ]));
    }

    /**
     * @Route(path="declenchement/{campaignId}/ouvrir/{csrf}", name="open_campaign")
     *
     * @param int    $campaignId
     * @param string $csrf
     *
     * @return Response
     */
    public function openCampaign(int $campaignId, string $csrf): Response
    {
        $this->validateCsrfOrThrowNotFoundException('campaign', $csrf);

        $campaign = $this->getCampaignOrThrowNotFoundExcpetion($campaignId);

        // Reopen the campaign
        if (!$campaign->isActive()) {
            $this->campaignManager->openCampaign($campaign);
        }

        return $this->redirect($this->generateUrl('communication_index', [
            'campaignId' => $campaign->getId(),
        ]));
    }

    /**
     * @Route(path="declenchement/{campaignId}/changer-couleur/{color}/{csrf}", name="color_campaign")
     *
     * @param int    $campaignId
     * @param string $csrf
     *
     * @return Response
     */
    public function changeColor(int $campaignId, string $color, string $csrf): Response
    {
        $this->validateCsrfOrThrowNotFoundException('campaign', $csrf);

        $campaignEntity = $this->getCampaignOrThrowNotFoundExcpetion($campaignId);

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
            'campaignId' => $campaignId,
        ]));
    }

    /**
     * @Route(path="declenchement/{campaignId}/changer-nom", name="rename_campaign")
     *
     * @param Request $request
     * @param int     $campaignId
     *
     * @return Response
     */
    public function rename(Request $request, int $campaignId): Response
    {
        $this->validateCsrfOrThrowNotFoundException('campaign', $request->request->get('csrf'));

        $campaignEntity = $this->getCampaignOrThrowNotFoundExcpetion($campaignId);

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
            'campaignId' => $campaignId,
        ]));
    }

    /**
     * @param int $campaignId
     *
     * @return Campaign
     *
     * @throws NotFoundHttpException
     */
    private function getCampaignOrThrowNotFoundExcpetion(int $campaignId): Campaign
    {
        $campaign = $this->get('doctrine')->getRepository('App:Campaign')->findOneBy([
            'id' => $campaignId,
        ]);

        if (is_null($campaign)) {
            throw $this->createNotFoundException();
        }

        return $campaign;
    }
}
