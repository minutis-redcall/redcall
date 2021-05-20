<?php

namespace App\Controller;

use App\Base\BaseController;
use App\Entity\Campaign;
use App\Enum\Type;
use App\Form\Flow\CampaignFlow;
use App\Form\Model\Campaign as CampaignModel;
use App\Form\Model\SmsTrigger;
use App\Manager\CampaignManager;
use App\Manager\OperationManager;
use App\Manager\PlatformConfigManager;
use App\Manager\StructureManager;
use Bundles\PaginationBundle\Manager\PaginationManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CampaignController extends BaseController
{
    /**
     * @var PaginationManager
     */
    private $paginationManager;

    /**
     * @var CampaignManager
     */
    private $campaignManager;

    /**
     * @var PlatformConfigManager
     */
    private $platformManager;

    /**
     * @var StructureManager
     */
    private $structureManager;

    /**
     * @var OperationManager
     */
    private $operationManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(PaginationManager $paginationManager,
        CampaignManager $campaignManager,
        PlatformConfigManager $platformManager,
        StructureManager $structureManager,
        OperationManager $operationManager,
        TranslatorInterface $translator)
    {
        $this->paginationManager = $paginationManager;
        $this->campaignManager   = $campaignManager;
        $this->platformManager   = $platformManager;
        $this->structureManager  = $structureManager;
        $this->operationManager  = $operationManager;
        $this->translator        = $translator;
    }

    /**
     * @Route(path="campaign/list", name="list_campaigns")
     */
    public function listCampaigns()
    {
        $byMyCrew      = $this->campaignManager->getCampaignsOpenedByMeOrMyCrew($this->getUser());
        $byMyTeammates = $this->campaignManager->getCampaignImpactingMyVolunteers($this->getUser());
        $finished      = $this->campaignManager->getInactiveCampaignsForUserQueryBuilder($this->getUser());

        return $this->render('campaign/list.html.twig', [
            'data' => [
                'my_structures' => [
                    'orderBy' => $this->orderBy($byMyCrew, Campaign::class, 'c.createdAt', 'DESC', 'crew'),
                    'pager'   => $this->paginationManager->getPager($byMyCrew, 'my_structures'),
                ],
                'my_volunteers' => [
                    'orderBy' => $this->orderBy($byMyTeammates, Campaign::class, 'c.createdAt', 'DESC', 'mates'),
                    'pager'   => $this->paginationManager->getPager($byMyTeammates, 'my_volunteers'),
                ],
                'finished'      => [
                    'orderBy' => $this->orderBy($finished, Campaign::class, 'c.createdAt', 'DESC', 'finished'),
                    'pager'   => $this->paginationManager->getPager($finished, 'finished'),
                ],
            ],
        ]);
    }

    /**
     * @Route(path="campaign/new/{type}", name="create_campaign")
     */
    public function createCampaign(Request $request, Type $type, CampaignFlow $flow)
    {
        $user = $this->getUser();

        if (!$user->getVolunteer() || !$user->getStructures()->count()) {
            return $this->redirectToRoute('home');
        }

        $campaignModel = new CampaignModel(
            $type->getFormData()
        );

        $campaignModel->trigger->setLanguage(
            $this->platformManager->getPlaform($this->getPlatform())->getDefaultLanguage()->getLocale()
        );

        $flow->bind($campaignModel);
        $form = $flow->createForm();

        if ($flow->isValid($form)) {
            $flow->saveCurrentStepData($form);

            if ($flow->nextStep()) {
                $form = $flow->createForm();
            } else {
                $campaignEntity = $this->campaignManager->launchNewCampaign($campaignModel);

                if (!$campaignEntity) {
                    return $this->redirectToRoute('home');
                }

                return $this->redirect($this->generateUrl('communication_index', [
                    'id' => $campaignEntity->getId(),
                ]));
            }
        }

        return $this->render('new_communication/new.html.twig', [
            'flow' => $flow,
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
     * @IsGranted("CAMPAIGN_OWNER", subject="campaign")
     */
    public function openCampaign(Campaign $campaign, string $csrf) : Response
    {
        $this->validateCsrfOrThrowNotFoundException('campaign', $csrf);

        if (!$campaign->isActive()) {
            if (!$this->campaignManager->canReopenCampaign($campaign)) {
                $this->addFlash('danger', $this->translator->trans('campaign.cannot_reopen'));
            } else {
                $this->campaignManager->openCampaign($campaign);
            }
        }

        return $this->redirect($this->generateUrl('communication_index', [
            'id' => $campaign->getId(),
        ]));
    }

    /**
     * @Route(path="campaign/{id}/keep/{csrf}", name="keep_campaign")
     * @IsGranted("CAMPAIGN_OWNER", subject="campaign")
     */
    public function keepCampaign(Campaign $campaign, string $csrf) : Response
    {
        $this->validateCsrfOrThrowNotFoundException('campaign', $csrf);

        if ($campaign->isActive()) {
            $this->campaignManager->postponeExpiration($campaign);
        }

        return $this->json([
            'expiresAt' => $campaign->getExpiresAt()->format('d/m/Y H:i'),
        ]);
    }

    /**
     * @Route(path="campaign/{id}/change-color/{color}/{csrf}", name="color_campaign")
     * @IsGranted("CAMPAIGN_OWNER", subject="campaignEntity")
     */
    public function changeColor(Campaign $campaignEntity,
        string $color,
        string $csrf,
        ValidatorInterface $validator) : Response
    {
        $this->validateCsrfOrThrowNotFoundException('campaign', $csrf);

        $campaign       = new CampaignModel(new SmsTrigger());
        $campaign->type = $color;
        $errors         = $validator->validate($campaign, null, ['color_edition']);
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
    public function rename(Request $request, Campaign $campaignEntity, ValidatorInterface $validator) : Response
    {
        $this->validateCsrfOrThrowNotFoundException('campaign', $request->request->get('csrf'));

        $campaign        = new CampaignModel(new SmsTrigger());
        $campaign->label = $request->request->get('new_name');
        $errors          = $validator->validate($campaign, null, ['label_edition']);
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
     * @IsGranted("CAMPAIGN_ACCESS", subject="campaign")
     */
    public function notes(Request $request, Campaign $campaign) : Response
    {
        $this->validateCsrfOrThrowNotFoundException('campaign', $request->request->get('csrf'));

        $notes = strip_tags($request->request->get('notes'));

        $this->campaignManager->changeNotes($campaign, $notes);

        return $this->redirect($this->generateUrl('communication_index', [
            'id' => $campaign->getId(),
        ]));
    }

    /**
     * @Route(path="campaign/{id}/report", name="campaign_report")
     * @IsGranted("CAMPAIGN_ACCESS", subject="campaign")
     * @Template
     */
    public function report(Campaign $campaign)
    {
        return [
            'campaign' => $campaign,
        ];
    }

    /**
     * @Route(path="campaign/operations", name="campaign_search_for_operation")
     */
    public function searchForOperation(Request $request)
    {
        $structure = $this->structureManager->findOneByExternalId($this->getPlatform(), $request->get('externalId'));
        if (!$structure || !$this->isGranted('STRUCTURE', $structure)) {
            throw $this->createNotFoundException();
        }

        $operations = array_map(function (array $operation) {
            return [
                'id'   => $operation['id'],
                'name' => strip_tags($operation['name']),
            ];
        }, $this->operationManager->listOperations($structure));

        return $this->json([
            'operations' => $operations,
        ]);
    }
}
