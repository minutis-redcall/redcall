<?php

namespace App\Manager;

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
        $campaignEntity = new CampaignEntity();
        $campaignEntity
            ->setLabel($campaignModel->label)
            ->setType($campaignModel->type)
            ->setNotes($campaignModel->notes)
            ->setNotesUpdatedAt($campaignModel->notes ? new \DateTime() : null)
            ->setActive(true)
            ->setCreatedAt(new \DateTime());

        $this->campaignRepository->save($campaignEntity);

        $this->communicationManager->launchNewCommunication($campaignEntity, $campaignModel->trigger);

        return $campaignEntity;
    }

    /**
     * @param Campaign $campaign
     *
     * @throws LogicException
     */
    public function closeCampaign(CampaignEntity $campaign)
    {
        $this->campaignRepository->closeCampaign($campaign);
    }

    /**
     * @param Campaign $campaign
     *
     * @throws LogicException
     */
    public function openCampaign(CampaignEntity $campaign)
    {
        $this->campaignRepository->openCampaign($campaign);
    }

    public function changeColor(CampaignEntity $campaign, string $color): void
    {
        $this->campaignRepository->changeColor($campaign, $color);
    }

    public function changeName(CampaignEntity $campaign, string $newName): void
    {
        $this->campaignRepository->changeName($campaign, $newName);
    }

    public function changeNotes(CampaignEntity $campaign, string $notes): void
    {
        $this->campaignRepository->changeNotes($campaign, $notes);
    }

    public function refresh(CampaignEntity $campaign)
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

    /**
     * We want the data to be refreshed on the frontend side only when the
     * campaign changes, so hash should contain all information that can
     * change (eg. messages sent, answers, note etc), in order to break
     * the long polling only when main information are updated.
     *
     * We could use the CampaignEntity object directly (iterating it in
     * order to count messages received etc) but it would lazily load
     * everything related to the campaign from the db, which is too heavy.
     *
     * We should also take care to explicitly disable Doctrine caching in
     * all queries, because Doctrine will usually return the same result
     * for the same query.
     *
     * @param int $campaignId
     *
     * @return string
     */
    public function getHash(int $campaignId) : string
    {
        $criteria = [
            // trigger note has been updated
            $this->campaignRepository->getNoteUpdateTimestamp($campaignId),

            // number of messages sent changed
            $this->campaignRepository->countNumberOfMessagesSent($campaignId),

            // number of answers to any of the trigger's communication increased
            // note: we don't need to take care of "answers that changed" because answers are immutable
            $this->campaignRepository->countNumberOfAnswersReceived($campaignId),

            // number of geolocation data increased
            $this->campaignRepository->countNumberOfGeoLocationReceived($campaignId),

            // geolocalisation data of any volunteer has been updated
            $this->campaignRepository->getLastGeoLocationUpdated($campaignId),
        ];

        return sha1(implode('|', $criteria));
    }
}