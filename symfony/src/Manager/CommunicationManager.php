<?php

namespace App\Manager;

use App\Communication\Processor\ProcessorInterface;
use App\Entity\Campaign;
use App\Entity\Choice;
use App\Entity\Communication;
use App\Entity\Message;
use App\Entity\Volunteer;
use App\Form\Model\BaseTrigger;
use App\Repository\CommunicationRepository;
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
     * @var UserManager
     */
    private $userManager;

    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @var AudienceManager
     */
    private $audienceManager;

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

    public function __construct(MessageManager $messageManager,
        CommunicationRepository $communicationRepository,
        ProcessorInterface $processor,
        UserManager $userManager,
        VolunteerManager $volunteerManager,
        AudienceManager $audienceManager,
        StructureManager $structureManager,
        RouterInterface $router,
        LoggerInterface $slackLogger,
        LoggerInterface $logger)
    {
        $this->messageManager          = $messageManager;
        $this->communicationRepository = $communicationRepository;
        $this->processor               = $processor;
        $this->userManager             = $userManager;
        $this->volunteerManager        = $volunteerManager;
        $this->audienceManager         = $audienceManager;
        $this->structureManager        = $structureManager;
        $this->router                  = $router;
        $this->slackLogger             = $slackLogger;
        $this->logger                  = $logger;
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

    public function find(int $communicationId) : ?Communication
    {
        return $this->communicationRepository->find($communicationId);
    }

    public function launchNewCommunication(Campaign $campaign,
        BaseTrigger $trigger,
        ProcessorInterface $processor = null) : Communication
    {
        $this->logger->info('Launching a new communication', [
            'model' => $trigger,
        ]);

        $communication = $this->createCommunication($trigger);
        $communication->setRaw(json_encode($trigger, JSON_PRETTY_PRINT));

        $campaign->addCommunication($communication);

        $this->campaignManager->save($campaign);

        $this->communicationRepository->save($communication);

        if ($processor) {
            $processor->process($communication);
        } else {
            $this->processor->process($communication);
        }

        $structureName = $communication->getVolunteer()->getMainStructure()->getDisplayName();
        if ($communication->getVolunteer()->getUser() && $communication->getVolunteer()->getUser()->getMainStructure()) {
            $structureName = $communication->getVolunteer()->getUser()->getMainStructure()->getDisplayName();
        }

        $this->slackLogger->info(
            sprintf(
                'New %s trigger by %s (%s) on %d volunteers from %d structures.%s%s%sLink: %s',
                strtoupper($communication->getType()),
                $communication->getVolunteer()->getDisplayName(),
                $structureName,
                count($communication->getMessages()),
                count($this->structureManager->getCampaignStructures($campaign)),
                PHP_EOL,
                $campaign->getLabel(),
                PHP_EOL,
                sprintf('%s%s', getenv('WEBSITE_URL'), $this->router->generate('communication_index', [
                    'id' => $campaign->getId(),
                ]))
            )
        );

        return $communication;
    }

    public function createCommunication(BaseTrigger $trigger) : Communication
    {
        $volunteer = null;
        if ($user = $this->userManager->findForCurrentUser()) {
            $id        = null;
            $volunteer = $user->getVolunteer();
        } else {
            // Triggers ran through the Campaign::contact() method only contain 1 volunteer
            $id        = $trigger->getAudience()['volunteers'][0];
            $volunteer = $this->volunteerManager->find($id);
        }

        $communication = new Communication();
        $communication
            ->setVolunteer($volunteer)
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

        if ($id) {
            $volunteers = [$volunteer];
        } else {
            $classification = $this->audienceManager->classifyAudience($trigger->getAudience());
            $volunteers     = $this->volunteerManager->getVolunteerList($classification->getReachable());
        }

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

            $this->handleUnreachable($communication->getType(), $message);

            $communication->addMessage($message);
        }

        return $communication;
    }

    public function changeName(Communication $communication, string $newName)
    {
        $this->communicationRepository->changeName($communication, $newName);
    }

    private function handleUnreachable(string $type, Message $message)
    {
        $error     = null;
        $volunteer = $message->getVolunteer();

        switch ($type) {
            case Communication::TYPE_SMS:
                if ($volunteer->getPhoneNumber() && !$volunteer->getPhone()->isMobile()) {
                    $error = 'campaign_status.warning.no_phone_mobile';
                    break;
                }
            case Communication::TYPE_CALL:
                if (null === $volunteer->getPhoneNumber()) {
                    $error = 'campaign_status.warning.no_phone';
                    break;
                }
                if (!$volunteer->isPhoneNumberOptin()) {
                    $error = 'campaign_status.warning.no_phone_optin';
                    break;
                }
                break;
            case Communication::TYPE_EMAIL:
                if (null === $volunteer->getEmail()) {
                    $error = 'campaign_status.warning.no_email';
                    break;
                }
                if (!$volunteer->isEmailOptin()) {
                    $error = 'campaign_status.warning.no_email_optin';
                    break;
                }
                break;
        }

        if (null !== $error) {
            $message->setError($error);
        }
    }
}