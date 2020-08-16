<?php

namespace App\Manager;

use App\Entity\Campaign;
use App\Entity\Campaign as CampaignEntity;
use App\Entity\Communication;
use App\Form\Model\Campaign as CampaignModel;
use App\Repository\CampaignRepository;
use Bundles\PasswordLoginBundle\Entity\AbstractUser;
use Doctrine\ORM\QueryBuilder;
use Exception;
use LogicException;

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
     * @var MessageManager
     */
    private $messageManager;

    /**
     * @param CampaignRepository   $campaignRepository
     * @param CommunicationManager $communicationManager
     * @param MessageManager       $messageManager
     */
    public function __construct(CampaignRepository $campaignRepository,
        CommunicationManager $communicationManager,
        MessageManager $messageManager)
    {
        $this->campaignRepository   = $campaignRepository;
        $this->communicationManager = $communicationManager;
        $this->messageManager       = $messageManager;
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
     * @throws Exception
     */
    public function launchNewCampaign(CampaignModel $campaignModel): CampaignEntity
    {
        $campaignEntity = new Campaign();
        $campaignEntity
            ->setLabel($campaignModel->label)
            ->setType($campaignModel->type)
            ->setNotes($campaignModel->notes)
            ->setNotesUpdatedAt(new \DateTime())
            ->setActive(true)
            ->setCreatedAt(new \DateTime());

        $this->campaignRepository->save($campaignEntity);

        $this->communicationManager->launchNewCommunication($campaignEntity, $campaignModel->communication);

        return $campaignEntity;
    }

    /**
     * @param Campaign $campaign
     *
     * @throws LogicException
     */
    public function closeCampaign(Campaign $campaign)
    {
        $this->campaignRepository->closeCampaign($campaign);
    }

    /**
     * @param Campaign $campaign
     *
     * @throws LogicException
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
     * @param AbstractUser $user
     *
     * @return QueryBuilder
     */
    public function getActiveCampaignsForUserQueryBuilder(AbstractUser $user): QueryBuilder
    {
        return $this->campaignRepository->getActiveCampaignsForUserQueryBuilder($user);
    }

    /**
     * @param AbstractUser $user
     *
     * @return QueryBuilder
     */
    public function getInactiveCampaignsForUserQueryBuilder(AbstractUser $user): QueryBuilder
    {
        return $this->campaignRepository->getInactiveCampaignsForUserQueryBuilder($user);
    }

    /**
     * A campaign can only be reopened if any of the prefix associated to everyone's
     * messages are not used in any of the currently open campaigns.
     *
     * @param CampaignEntity $campaign
     *
     * @return bool
     */
    public function canReopenCampaign(CampaignEntity $campaign): bool
    {
        // Fetch all taken prefixes for all called volunteers
        $volunteersTakenPrefixes = [];
        foreach ($campaign->getCommunications() as $communication) {
            /** @var Communication $communication */
            foreach ($communication->getMessages() as $message) {
                if (!array_key_exists($message->getVolunteer()->getId(), $volunteersTakenPrefixes)) {
                    $volunteersTakenPrefixes[$message->getVolunteer()->getId()] = [];
                }

                $volunteersTakenPrefixes[$message->getVolunteer()->getId()][] = $message->getPrefix();
            }
        }

        return $this->messageManager->canUsePrefixesForEveryone($volunteersTakenPrefixes);
    }

    /**
     * Return all active campaign
     *
     * @return QueryBuilder
     */
    public function getAllOpenCampaignsQueryBuilder()
    {
        return $this->campaignRepository->getActiveCampaignsQueryBuilder();
    }

    public function getAllCampaignsQueryBuilder() : QueryBuilder
    {
        return $this->campaignRepository->getAllCampaignsQueryBuilder();
    }

    /**
     * @return int
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countAllOpenCampaigns(): int
    {
        return $this->campaignRepository->countAllOpenCampaigns();
    }

    /**
     * @param int $days
     *
     * @return array
     */
    public function findInactiveCampaignsSince(int $days): array
    {
        return $this->campaignRepository->findInactiveCampaignsSince($days);
    }
}