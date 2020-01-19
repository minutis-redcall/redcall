<?php

namespace App\Manager;

use App\Entity\Campaign;
use App\Entity\Campaign as CampaignEntity;
use App\Form\Model\Campaign as CampaignModel;
use App\Repository\CampaignRepository;
use Bundles\PasswordLoginBundle\Entity\User;
use Doctrine\ORM\QueryBuilder;

class CampaignManager
{
    /**
     * @var CampaignRepository
     */
    private $campaignRepository;

    /**
     * @var CommunicationManager
     */
    private $communicationManager;

    /**
     * @param CampaignRepository   $campaignRepository
     * @param CommunicationMAnager $communicationManager ;
     */
    public function __construct(
        CampaignRepository $campaignRepository,
        CommunicationManager $communicationManager
    ) {
        $this->campaignRepository   = $campaignRepository;
        $this->communicationManager = $communicationManager;
    }

    /**
     * @param int $campaignId
     *
     * @return CampaignEntity|null
     */
    public function find(int $campaignId): ?CampaignEntity
    {
        return $this->campaignRepository->find($campaignId);
    }

    /**
     * @param CampaignEntity $campaign
     */
    public function save(CampaignEntity $campaign)
    {
        $this->campaignRepository->save($campaign);
    }

    /**
     * Launches a campaign by creating a new one and sending an initial communication to a list of volunteers.
     *
     * @param CampaignModel $campaignModel
     *
     * @return CampaignEntity
     *
     * @throws \Exception
     */
    public function launchNewCampaign(CampaignModel $campaignModel): CampaignEntity
    {
        $campaignEntity = new Campaign();
        $campaignEntity
            ->setLabel($campaignModel->label)
            ->setType($campaignModel->type)
            ->setActive(true)
            ->setCreatedAt(new \DateTime());

        $this->campaignRepository->save($campaignEntity);

        $this->communicationManager->launchNewCommunication($campaignEntity, $campaignModel->communication);

        return $campaignEntity;
    }

    /**
     * @param Campaign $campaign
     *
     * @throws \LogicException
     */
    public function closeCampaign(Campaign $campaign)
    {
        $this->campaignRepository->closeCampaign($campaign);
    }

    /**
     * @param Campaign $campaign
     *
     * @throws \LogicException
     */
    public function openCampaign(Campaign $campaign)
    {
        $this->campaignRepository->openCampaign($campaign);
    }

    /**
     * @param Campaign $campaign
     * @param string   $color
     */
    public function changeColor(Campaign $campaign, string $color): void
    {
        $this->campaignRepository->changeColor($campaign, $color);
    }

    /**
     * @param Campaign $campaign
     * @param string   $newName
     */
    public function changeName(Campaign $campaign, string $newName): void
    {
        $this->campaignRepository->changeName($campaign, $newName);
    }

    /**
     * @param Campaign $campaign
     */
    public function refresh(Campaign $campaign)
    {
        return $this->campaignRepository->findOneByIdNoCache($campaign->getId());
    }

    /**
     * @param User $user
     *
     * @return QueryBuilder
     */
    public function getActiveCampaignsForUserQueryBuilder(User $user): QueryBuilder
    {
        return $this->campaignRepository->getActiveCampaignsQueryBuilder($user);
    }

    /**
     * @param User $user
     *
     * @return QueryBuilder
     */
    public function getInactiveCampaignsForUserQueryBuilder(User $user): QueryBuilder
    {
        return $this->campaignRepository->getInactiveCampaignsQueryBuilder($user);
    }
}