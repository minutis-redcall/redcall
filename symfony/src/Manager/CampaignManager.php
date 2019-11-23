<?php

namespace App\Manager;

use App\Communication\Dispatcher;
use App\Entity\Campaign;
use App\Entity\Campaign as CampaignEntity;
use App\Form\Model\Campaign as CampaignModel;
use App\Repository\CampaignRepository;
use Doctrine\ORM\EntityManagerInterface;

class CampaignManager
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var Dispatcher */
    private $communicationDispatcher;

    /** @var CampaignRepository */
    private $campaignRepository;

    /**
     * @var CommunicationManager
     */
    private $communicationManager;

    /**
     * CampaignManager constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param Dispatcher             $communicationDispatcher
     * @param CampaignRepository     $campaignRepository
     * @param CommunicationMAnager   $communicationManager;
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        Dispatcher $communicationDispatcher,
        CampaignRepository $campaignRepository,
        CommunicationManager $communicationManager
    ) {
        $this->entityManager           = $entityManager;
        $this->communicationDispatcher = $communicationDispatcher;
        $this->campaignRepository      = $campaignRepository;
        $this->communicationManager    = $communicationManager;
    }

    /**
     * Launches a campaign by creating a new one and sending an initial communication to a list of volunteers.
     *
     * @param CampaignModel $campaignModel
     *
     * @return CampaignEntity
     */
    public function launchNewCampaign(CampaignModel $campaignModel) : CampaignEntity
    {
        $communication = $this->communicationManager->createCommunication($campaignModel->communication);

        $campaignEntity = new Campaign();
        $campaignEntity
            ->setLabel($campaignModel->label)
            ->setType($campaignModel->type)
            ->setActive(true)
            ->setCreatedAt(new \DateTime())
            ->addCommunication($communication)
        ;

        $this->campaignRepository->save($campaignEntity);

        $this->communicationManager->dispatch($communication);

        return $campaignEntity;
    }

    /**
     * @param Campaign $campaign
     *
     * @throws \LogicException
     */
    public function closeCampaign(Campaign $campaign)
    {
        if (!$campaign->isActive()) {
            throw new \LogicException('Campaign was already closed');
        }

        $campaign->setActive(false);
        $this->entityManager->flush();
    }

    /**
     * @param Campaign $campaign
     *
     * @throws \LogicException
     */
    public function openCampaign(Campaign $campaign)
    {
        if ($campaign->isActive()) {
            throw new \LogicException('Campaign was already closed');
        }

        $campaign->setActive(true);
        $this->entityManager->flush();
    }

    /**
     * @param Campaign $campaign
     * @param string   $color
     */
    public function changeColor(Campaign $campaign, string $color): void
    {
        $campaign->setType($color);
        $this->entityManager->flush();
    }

    /**
     * @param Campaign $campaign
     * @param string   $newName
     */
    public function changeName(Campaign $campaign, string $newName): void
    {
        $campaign->setLabel($newName);
        $this->entityManager->flush();
    }

    /**
     * @param Campaign $campaign
     */
    public function refresh(Campaign $campaign)
    {
        $this->entityManager->clear();

        return $this->campaignRepository->findOneByIdNoCache($campaign->getId());
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getActiveCampaignsQueryBuilder()
    {
        return $this->campaignRepository->getActiveCampaignsQueryBuilder();
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getInactiveCampaignsQueryBuilder()
    {
        return $this->campaignRepository->getInactiveCampaignsQueryBuilder();
    }
}