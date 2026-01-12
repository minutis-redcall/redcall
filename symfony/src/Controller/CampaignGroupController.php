<?php

namespace App\Controller;

use App\Base\BaseController;
use App\Entity\Campaign;
use App\Entity\Volunteer;
use App\Entity\VolunteerGroup;
use App\Manager\CampaignManager;
use App\Repository\VolunteerGroupRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="campaign/{id}/group", name="campaign_group_", requirements={"id" = "\d+"})
 * @IsGranted("CAMPAIGN_ACCESS", subject="campaign")
 */
class CampaignGroupController extends BaseController
{
    private $campaignManager;
    private $volunteerGroupRepository;

    public function __construct(CampaignManager $campaignManager, VolunteerGroupRepository $volunteerGroupRepository)
    {
        $this->campaignManager          = $campaignManager;
        $this->volunteerGroupRepository = $volunteerGroupRepository;
    }

    /**
     * @Route(path="/rename/{index}", name="rename", methods={"POST"})
     */
    public function rename(Campaign $campaign, int $index, Request $request)
    {
        $this->validateCsrfOrThrowNotFoundException('campaign', $request->get('csrf'));

        $name          = trim($request->request->get('name'));
        $names         = $campaign->getGroupNames();
        $names[$index] = $name ?: null;
        $campaign->setGroupNames($names);

        $this->campaignManager->save($campaign);

        return new JsonResponse(['success' => true]);
    }

    /**
     * @Route(path="/volunteer/{volunteerId}/toggle/{index}", name="toggle", methods={"POST"})
     * @Entity("volunteer", expr="repository.find(volunteerId)")
     */
    public function toggle(Campaign $campaign, Volunteer $volunteer, int $index, Request $request)
    {
        $this->validateCsrfOrThrowNotFoundException('campaign', $request->get('csrf'));

        $volunteerGroup = $this->volunteerGroupRepository->findOneBy([
            'campaign'   => $campaign,
            'volunteer'  => $volunteer,
            'groupIndex' => $index,
        ]);

        error_log(sprintf('Toggling group %d for campaign %d and volunteer %d', $index, $campaign->getId(), $volunteer->getId()));

        if ($volunteerGroup) {
            error_log('Removing existing volunteer group');
            $this->volunteerGroupRepository->remove($volunteerGroup, true);
        } else {
            error_log('Creating new volunteer group');
            $volunteerGroup = new VolunteerGroup();
            $volunteerGroup->setCampaign($campaign);
            $volunteerGroup->setVolunteer($volunteer);
            $volunteerGroup->setGroupIndex($index);
            $this->volunteerGroupRepository->save($volunteerGroup, true);
        }

        return new JsonResponse(['success' => true]);
    }
}
