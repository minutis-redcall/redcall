<?php

namespace App\Manager;

use App\Communication\Processor\ProcessorInterface;
use App\Communication\Processor\SimpleProcessor;
use App\Entity\Campaign;
use App\Entity\Campaign as CampaignEntity;
use App\Entity\Communication;
use App\Entity\User;
use App\Entity\Volunteer;
use App\Enum\Type;
use App\Form\Model\Campaign as CampaignModel;
use App\Form\Type\AudienceType;
use App\Repository\CampaignRepository;
use Bundles\PasswordLoginBundle\Entity\AbstractUser;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

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
     * @var StructureManager
     */
    private $structureManager;

    /**
     * @var SimpleProcessor
     */
    private $processor;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(CampaignRepository $campaignRepository,
        CommunicationManager $communicationManager,
        MessageManager $messageManager,
        StructureManager $structureManager,
        SimpleProcessor $processor,
        TokenStorageInterface $tokenStorage)
    {
        $this->campaignRepository   = $campaignRepository;
        $this->communicationManager = $communicationManager;
        $this->messageManager       = $messageManager;
        $this->structureManager     = $structureManager;
        $this->processor            = $processor;
        $this->tokenStorage         = $tokenStorage;
    }

    public function find(int $campaignId) : ?CampaignEntity
    {
        return $this->campaignRepository->find($campaignId);
    }

    public function save(CampaignEntity $campaign)
    {
        $this->campaignRepository->save($campaign);
    }

    public function launchNewCampaign(CampaignModel $campaignModel,
        ProcessorInterface $processor = null) : ?CampaignEntity
    {
        $volunteer = null;
        if ($this->tokenStorage->getToken()->getUser() instanceof UserInterface) {
            $volunteer = $this->tokenStorage->getToken()->getUser()->getVolunteer();
            if (!$volunteer) {
                return null;
            }
        }

        $campaignEntity = new CampaignEntity();
        $campaignEntity
            ->setVolunteer($volunteer)
            ->setLabel($campaignModel->label)
            ->setType($campaignModel->type)
            ->setNotes($campaignModel->notes)
            ->setNotesUpdatedAt($campaignModel->notes ? new \DateTime() : null)
            ->setExpiresAt((new \DateTime())->add(new \DateInterval('P7D')))
            ->setActive(true)
            ->setCreatedAt(new \DateTime());

        $this->campaignRepository->save($campaignEntity);

        $this->communicationManager->launchNewCommunication($campaignEntity, $campaignModel->trigger, $processor);

        return $campaignEntity;
    }

    public function closeCampaign(CampaignEntity $campaign)
    {
        $this->campaignRepository->closeCampaign($campaign);
    }

    public function openCampaign(CampaignEntity $campaign)
    {
        $this->campaignRepository->openCampaign($campaign);
    }

    public function postponeExpiration(CampaignEntity $campaign)
    {
        $tm = $campaign->getExpiresAt()->getTimestamp();
        if ($tm < time()) {
            $tm = time() + Campaign::DEFAULT_EXPIRATION;
        } else {
            $tm = $tm + Campaign::DEFAULT_EXPIRATION;
        }
        $campaign->setExpiresAt((new \DateTime())->setTimestamp($tm));

        $this->campaignRepository->save($campaign);
    }

    public function changeColor(CampaignEntity $campaign, string $color) : void
    {
        $this->campaignRepository->changeColor($campaign, $color);
    }

    public function changeName(CampaignEntity $campaign, string $newName) : void
    {
        $this->campaignRepository->changeName($campaign, $newName);
    }

    public function changeNotes(CampaignEntity $campaign, string $notes) : void
    {
        $this->campaignRepository->changeNotes($campaign, $notes);
    }

    public function refresh(CampaignEntity $campaign)
    {
        return $this->campaignRepository->findOneByIdNoCache($campaign->getId());
    }

    public function getCampaignsOpenedByMeOrMyCrew(User $user) : QueryBuilder
    {
        return $this->campaignRepository->getCampaignsOpenedByMeOrMyCrew($user);
    }

    public function getCampaignImpactingMyVolunteers(User $user) : QueryBuilder
    {
        return $this->campaignRepository->getCampaignImpactingMyVolunteers($user);
    }

    public function getInactiveCampaignsForUserQueryBuilder(AbstractUser $user) : QueryBuilder
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
    public function canReopenCampaign(CampaignEntity $campaign) : bool
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
    public function countAllOpenCampaigns() : int
    {
        return $this->campaignRepository->countAllOpenCampaigns();
    }

    public function closeExpiredCampaigns()
    {
        $this->campaignRepository->closeExpiredCampaigns();
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
     * @TODO we now have Communicaiton.lastActivityAt, use it instead (update CommunicationActivitySubscriber with
     *       Campaign changes if necessary)
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

    public function getCampaignAudience(CampaignEntity $campaign) : array
    {
        return $this->campaignRepository->getCampaignAudience($campaign);
    }

    public function contact(Volunteer $volunteer, Type $type, string $title, string $message) : CampaignEntity
    {
        $communication = $type->getFormData();

        // TODO: choose the right language according to the platform once they will be integrated
        $communication->setLanguage('fr');

        $communication->setAudience(AudienceType::createEmptyData([
            'volunteers' => [$volunteer->getId()],
        ]));
        $communication->setMessage($message);

        $campaign        = new CampaignModel($communication);
        $campaign->label = $title;

        return $this->launchNewCampaign($campaign, $this->processor);
    }
}