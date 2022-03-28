<?php

namespace App\Manager;

use App\Communication\Processor\ProcessorInterface;
use App\Entity\Campaign;
use App\Entity\Choice;
use App\Entity\Communication;
use App\Entity\Message;
use App\Entity\User;
use App\Entity\Volunteer;
use App\Form\Model\BaseTrigger;
use App\Form\Model\EmailTrigger;
use App\Provider\Minutis\MinutisProvider;
use App\Repository\CommunicationRepository;
use App\Security\Helper\Security;
use DateTime;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;

class CommunicationManager
{
    /**
     * @var CampaignManager
     */
    private $campaignManager;

    /**
     * @var MessageManager
     */
    private $messageManager;

    /**
     * @var StructureManager
     */
    private $structureManager;

    /**
     * @var CommunicationRepository
     */
    private $communicationRepository;

    /**
     * @var ProcessorInterface
     */
    private $processor;

    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @var AudienceManager
     */
    private $audienceManager;

    /**
     * @var OperationManager
     */
    private $operationManager;

    /**
     * @var MinutisProvider
     */
    private $minutis;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var LoggerInterface
     */
    private $slackLogger;

    /**
     * @var Security
     */
    private $security;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(MessageManager $messageManager,
        StructureManager $structureManager,
        CommunicationRepository $communicationRepository,
        ProcessorInterface $processor,
        VolunteerManager $volunteerManager,
        AudienceManager $audienceManager,
        OperationManager $operationManager,
        MinutisProvider $minutis,
        RouterInterface $router,
        LoggerInterface $slackLogger,
        Security $security,
        LoggerInterface $logger)
    {
        $this->messageManager          = $messageManager;
        $this->structureManager        = $structureManager;
        $this->communicationRepository = $communicationRepository;
        $this->processor               = $processor;
        $this->volunteerManager        = $volunteerManager;
        $this->audienceManager         = $audienceManager;
        $this->operationManager        = $operationManager;
        $this->minutis                 = $minutis;
        $this->router                  = $router;
        $this->slackLogger             = $slackLogger;
        $this->security                = $security;
        $this->logger                  = $logger;
    }

    /**
     * @required
     */
    public function setCampaignManager(CampaignManager $campaignManager)
    {
        $this->campaignManager = $campaignManager;
    }

    public function find(int $communicationId) : ?Communication
    {
        return $this->communicationRepository->find($communicationId);
    }

    public function createNewCommunication(Campaign $campaign, BaseTrigger $trigger) : Communication
    {
        $communication = $this->createCommunication($trigger);
        $communication->setRaw(json_encode($trigger, JSON_PRETTY_PRINT));

        $campaign->addCommunication($communication);

        $this->operationManager->addChoicesToOperation($communication, $trigger);

        $this->campaignManager->save($campaign);

        $this->communicationRepository->save($communication);

        return $communication;
    }

    public function launchNewCommunication(Campaign $campaign, Communication $communication) : Communication
    {
        $this->processor->process($communication);

        if ($communication->getVolunteer()->getUser() && $communication->getVolunteer()->getUser()->getMainStructure()) {
            $structureName = $communication->getVolunteer()->getUser()->getMainStructure()->getDisplayName();
        } elseif ($communication->getVolunteer()->getMainStructure()) {
            $structureName = $communication->getVolunteer()->getMainStructure()->getDisplayName();
        } else {
            $structureName = '?';
        }

        try {
            $this->slackLogger->info(
                sprintf(
                    'New %s trigger by %s (%s) on %d volunteers from %d structures.%s%s%sLink: %s%s%s',
                    strtoupper($communication->getType()),
                    $communication->getVolunteer()->getDisplayName(),
                    $structureName,
                    count($communication->getMessages()),
                    count($this->structureManager->getCampaignStructures($campaign->getPlatform(), $campaign)),
                    PHP_EOL,
                    $campaign->getLabel(),
                    PHP_EOL,
                    sprintf('%s%s', getenv('WEBSITE_URL'), $this->router->generate('communication_index', [
                        'id' => $campaign->getId(),
                    ])),
                    $campaign->getOperation() ? PHP_EOL : '',
                    $campaign->getOperation() ? sprintf('Operation: %s', $campaign->getOperationUrl($this->minutis)) : ''
                )
            );
        } catch (\Throwable $exception) {
            $this->logger->warning('Cannot reach out Slack', [
                'campaign-id'      => $campaign->getId(),
                'communication-id' => $communication->getId(),
            ]);
        }

        return $communication;
    }

    public function createCommunication(BaseTrigger $trigger) : Communication
    {
        /** @var User|null $user */
        if ($user = $this->security->getUser()) {
            $id        = null;
            $volunteer = $user->getVolunteer();
            $platform  = $user->getPlatform();
        } else {
            // Triggers ran through the Campaign::contact() method only contain 1 volunteer
            $id        = $trigger->getAudience()['volunteers'][0];
            $volunteer = $this->volunteerManager->find($id);
            $platform  = $volunteer->getPlatform();
        }

        $communication = new Communication();
        $communication
            ->setVolunteer($volunteer)
            ->setShortcut($trigger->getShortcut())
            ->setType($trigger->getType())
            ->setLanguage($trigger->getLanguage())
            ->setBody($trigger->getMessage())
            ->setCreatedAt(new DateTime())
            ->setMultipleAnswer($trigger->isMultipleAnswer());

        if ($trigger instanceof EmailTrigger) {
            $communication->setSubject($trigger->getSubject());

            foreach ($trigger->getImages() as $image) {
                $communication->addImage($image);
            }
        }

        // The first choice key is always "1"
        $choiceKey = 1;
        foreach (array_unique($trigger->getAnswers()) as $choiceValue) {
            $choice = new Choice();
            $choice
                ->setCode($choiceKey)
                ->setLabel($choiceValue);

            $communication->addChoice($choice);
            $choiceKey++;
        }

        if ($id) {
            $volunteers = [$volunteer];
        } else {
            $classification = $this->audienceManager->classifyAudience($platform, $trigger->getAudience());
            $volunteers     = $this->volunteerManager->getVolunteerList($platform, $classification->getReachable());
        }

        $codes = $this->messageManager->generateCodes(count($volunteers));

        $prefixes = [];
        if (1 !== $choiceKey) {
            $prefixes = $this->messageManager->generatePrefixes($volunteers);
        }

        $volunteers = $this->sortAudienceByTriggeringPriority($volunteers);
        foreach ($volunteers as $volunteer) {
            $message = new Message();

            if (1 !== $choiceKey) {
                $message->setPrefix($prefixes[$volunteer->getId()]);
            }

            $code = array_pop($codes);

            $message->setCode($code);
            $message->setVolunteer($volunteer);

            $communication->addMessage($message);
        }

        return $communication;
    }

    public function changeName(Communication $communication, string $newName)
    {
        $this->communicationRepository->changeName($communication, $newName);
    }

    public function findCommunicationIdsRequiringReports() : array
    {
        return $this->communicationRepository->findCommunicationIdsRequiringReports(
            (new \DateTime())->sub(new \DateInterval('P1D'))
        );
    }

    public function clearEntityManager()
    {
        $this->communicationRepository->clearEntityManager();
    }

    public function getCommunicationStructures(Communication $communication) : array
    {
        return $this->communicationRepository->getCommunicationStructures($communication);
    }

    public function save(Communication $communication)
    {
        $this->communicationRepository->save($communication);
    }

    public function sortAudienceByTriggeringPriority(array $mixedVolunteers)
    {
        $mixedIds = array_map(function (Volunteer $volunteer) {
            return $volunteer->getId();
        }, $mixedVolunteers);

        $orderedIds = $this->volunteerManager->orderVolunteerIdsByTriggeringPriority($mixedIds);

        $orderedVolunteers = [];
        foreach ($orderedIds as $id) {
            $orderedVolunteers[] = $mixedVolunteers[$id];
        }

        return array_filter($orderedVolunteers);
    }
}