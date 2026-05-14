<?php

namespace App\Manager;

use App\Communication\Processor\ProcessorInterface;
use App\Entity\Campaign;
use App\Entity\Campaign as CampaignEntity;
use App\Entity\Communication;
use App\Entity\User;
use App\Entity\Volunteer;
use App\Form\Model\Campaign as CampaignModel;
use App\Repository\CampaignRepository;
use App\Repository\VolunteerGroupRepository;
use App\Tools\Random;
use Bundles\PasswordLoginBundle\Entity\AbstractUser;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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
     * @var OperationManager
     */
    private $operationManager;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var MailManager
     */
    private                          $mailManager;
    private VolunteerGroupRepository $volunteerGroupRepository;

    public function __construct(CampaignRepository $campaignRepository,
        CommunicationManager $communicationManager,
        MessageManager $messageManager,
        OperationManager $operationManager,
        TokenStorageInterface $tokenStorage,
        TranslatorInterface $translator,
        RouterInterface $router,
        MailManager $mailManager,
        VolunteerGroupRepository $volunteerGroupRepository)
    {
        $this->campaignRepository       = $campaignRepository;
        $this->communicationManager     = $communicationManager;
        $this->messageManager           = $messageManager;
        $this->operationManager         = $operationManager;
        $this->tokenStorage             = $tokenStorage;
        $this->translator               = $translator;
        $this->router                   = $router;
        $this->mailManager              = $mailManager;
        $this->volunteerGroupRepository = $volunteerGroupRepository;
    }

    public function find(int $campaignId) : ?CampaignEntity
    {
        return $this->campaignRepository->find($campaignId);
    }

    public function launchNewCampaign(CampaignModel $campaignModel,
        ?ProcessorInterface $processor = null,
        ?Volunteer $volunteer = null) : ?CampaignEntity
    {
        if (!$volunteer && ($this->tokenStorage->getToken()->getUser() instanceof UserInterface)) {
            $volunteer = $this->tokenStorage->getToken()->getUser()->getVolunteer();
            if (!$volunteer) {
                return null;
            }
        }

        do {
            $code = Random::generate(CampaignRepository::CODE_SIZE);
        } while ($this->campaignRepository->findOneByCode($code));

        $campaignEntity = new CampaignEntity();
        $campaignEntity
            ->setVolunteer($volunteer)
            ->setCode($code)
            ->setLabel($campaignModel->label)
            ->setType($campaignModel->type)
            ->setExpiresAt((new \DateTime())->add(new \DateInterval('P7D')))
            ->setActive(true)
            ->setCreatedAt(new \DateTime());

        $communication = $this->communicationManager->createCommunicationEntityFromTrigger($campaignModel->trigger);
        if (0 === $communication->getMessageCount()) {
            throw new NotFoundHttpException('New communication has no message');
        }

        $this->campaignRepository->save($campaignEntity);

        if ($campaignModel->hasOperation) {
            if (CampaignModel::CREATE_OPERATION === $campaignModel->createOperation) {
                $this->operationManager->createOperation($campaignModel, $campaignEntity);
            } elseif ($this->operationManager->canBindOperation($volunteer, $campaignModel)) {
                $this->operationManager->bindOperation($campaignModel, $campaignEntity);
            }

            $this->campaignRepository->save($campaignEntity);
        }

        $communication = $this->communicationManager->createNewCommunication($campaignEntity, $campaignModel->trigger, $communication);

        $this->communicationManager->launchNewCommunication($campaignEntity, $communication, $processor);

        if ($communication->getMessageCount() > 1 && $volunteer->getEmail()) {
            $locale  = 'fr';
            $url     = sprintf('%s%s', getenv('WEBSITE_URL'), $this->router->generate('synthesis_index', ['code' => $campaignEntity->getCode()]));
            $subject = $this->translator->trans('synthesis.email.subject', ['%label%' => $campaignEntity->getLabel()], null, $locale);
            $body    = $this->translator->trans('synthesis.email.content', ['%url%' => $url], null, $locale);
            $this->mailManager->simple($volunteer->getEmail(), $subject, $body, $body, $locale);
        }

        return $campaignEntity;
    }

    public function findOneByCode(string $code) : ?Campaign
    {
        return $this->campaignRepository->findOneByCode($code);
    }

    public function save(CampaignEntity $campaign)
    {
        $this->campaignRepository->save($campaign);
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

    public function refresh(CampaignEntity $campaign) : ?CampaignEntity
    {
        return $this->campaignRepository->findCampaignWithFreshData($campaign->getId());
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

    public function getAllCampaignsQueryBuilder() : QueryBuilder
    {
        return $this->campaignRepository->getAllCampaignsQueryBuilder();
    }

    public function countAllOpenCampaigns() : int
    {
        return $this->campaignRepository->countAllOpenCampaigns();
    }

    public function closeExpiredCampaigns()
    {
        $this->campaignRepository->closeExpiredCampaigns();
    }

    /**
     * Hash based on Campaign.lastActivityAt and Campaign.notesUpdatedAt.
     * These timestamps are updated by CommunicationActivitySubscriber (postFlush)
     * and manual write paths (notes, group toggle/rename).
     *
     * Single PK lookup query, replacing the previous 4 uncached JOIN queries.
     */
    public function getHash(int $campaignId) : string
    {
        $row = $this->campaignRepository->getHashData($campaignId);

        if (!$row) {
            return sha1('empty');
        }

        return sha1(implode('|', [
            $row['lastActivityAt'] instanceof \DateTimeInterface ? $row['lastActivityAt']->format('U.u') : '0',
            $row['notesUpdatedAt'] instanceof \DateTimeInterface ? $row['notesUpdatedAt']->format('U.u') : '0',
        ]));
    }

    public function getCampaignAudience(CampaignEntity $campaign) : array
    {
        return $this->campaignRepository->getCampaignAudience($campaign);
    }
}