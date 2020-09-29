<?php

namespace App\Manager;

use App\Communication\Processor\ProcessorInterface;
use App\Entity\Campaign;
use App\Entity\Choice;
use App\Entity\Communication;
use App\Entity\Message;
use App\Entity\Volunteer;
use App\Form\Model\BaseTrigger;
use App\Form\Model\Communication as CommunicationModel;
use App\Repository\CommunicationRepository;
use DateTime;
use Exception;
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
     * @var CommunicationRepository
     */
    private $communicationRepository;

    /**
     * @var ProcessorInterface
     */
    private $processor;

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var LoggerInterface
     */
    private $slackLogger;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param MessageManager          $messageManager
     * @param CommunicationRepository $communicationRepository
     * @param ProcessorInterface      $processor
     * @param UserManager             $userManager
     * @param VolunteerManager        $volunteerManager
     * @param RouterInterface         $router
     * @param LoggerInterface         $slackLogger
     * @param LoggerInterface         $logger
     */
    public function __construct(MessageManager $messageManager, CommunicationRepository $communicationRepository, ProcessorInterface $processor, UserManager $userManager, VolunteerManager $volunteerManager, RouterInterface $router, LoggerInterface $slackLogger, LoggerInterface $logger)
    {
        $this->messageManager = $messageManager;
        $this->communicationRepository = $communicationRepository;
        $this->processor = $processor;
        $this->userManager = $userManager;
        $this->volunteerManager = $volunteerManager;
        $this->router = $router;
        $this->slackLogger = $slackLogger;
        $this->logger = $logger;
    }

    /**
     * @required
     *
     * @param CampaignManager $campaignManager
     */
    public function setCampaignManager(CampaignManager $campaignManager)
    {
        $this->campaignManager = $campaignManager;
    }

    /**
     * @param int $communicationId
     *
     * @return Communication|null
     */
    public function find(int $communicationId): ?Communication
    {
        return $this->communicationRepository->find($communicationId);
    }

    public function launchNewCommunication(Campaign $campaign,
        CommunicationModel $communicationModel): Communication
    {
        $this->logger->info('Launching a new communication', [
            'model' => $communicationModel,
        ]);

        $Communication = $this->createCommunication($communicationModel);
        $Communication->setRaw(json_encode($communicationModel, JSON_PRETTY_PRINT));

        $campaign->addCommunication($Communication);
        foreach ($this->userManager->getCurrentUserStructures() as $structure) {
            $campaign->addStructure($structure);
        }

        $this->campaignManager->save($campaign);

        $this->processor->process($Communication);

        $this->communicationRepository->save($Communication);

        $this->slackLogger->info(
            sprintf(
                'New %s trigger by %s (%s) on %d volunteers from %d structures.%s%s%sLink: %s',
                strtoupper($Communication->getType()),
                $Communication->getVolunteer()->getDisplayName(),
                $Communication->getVolunteer()->getMainStructure()->getDisplayName(),
                count($Communication->getMessages()),
                $campaign->getStructures()->count(),
                PHP_EOL,
                $campaign->getLabel(),
                PHP_EOL,
                sprintf('%s%s', getenv('WEBSITE_URL'), $this->router->generate('communication_index', [
                    'id' => $campaign->getId(),
                ]))
            )
        );

        return $Communication;
    }

    /**
     * @param BaseTrigger $trigger
     *
     * @return Communication
     *
     * @throws Exception
     */
    public function createCommunication(BaseTrigger $trigger): Communication
    {
        $communication = new Communication();
        $communication
            ->setVolunteer(
                $this->userManager->findForCurrentUser()->getVolunteer()
            )
            ->setType($trigger->getType())
            ->setBody($trigger->getMessage())
            ->setGeoLocation($trigger->isGeoLocation())
            ->setCreatedAt(new DateTime())
            ->setMultipleAnswer($trigger->isMultipleAnswer())
            ->setSubject($trigger->getSubject());

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

        $volunteers = $this->volunteerManager->filterByNivolAndAccess($trigger->getAudience());
        $codes = $this->messageManager->generateCodes(count($volunteers));

        $prefixes = [];
        if (1 !== $choiceKey) {
            $prefixes = $this->messageManager->generatePrefixes($volunteers);
        }

        foreach ($volunteers as $volunteer) {
            /** @var Volunteer $volunteer */
            if (!$volunteer->isEnabled()) {
                // Useless but keep it as a safeguard
                continue;
            }

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

    /**
     * @return array
     */
    public function getTakenPrefixes(): array
    {
        return $this->communicationRepository->getTakenPrefixes();
    }

    /**
     * @param \App\Manager\Communication $communication
     * @param string                     $newName
     */
    public function changeName(Communication $communication, string $newName)
    {
        $this->communicationRepository->changeName($communication, $newName);
    }
}