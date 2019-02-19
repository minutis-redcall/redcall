<?php

namespace App\Campaign;

use App\Communication\CommunicationFactory;
use App\Communication\Dispatcher;
use App\Entity\Campaign;
use App\Repository\CampaignRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;

class CampaignManager
{
    /** @var CampaignFactory */
    private $campaignFactory;

    /** @var CommunicationFactory */
    private $communicationFactory;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var Dispatcher */
    private $communicationDispatcher;

    /** @var CampaignRepository */
    private $campaignRepository;

    /**
     * CampaignManager constructor.
     *
     * @param CampaignFactory        $campaignFactory
     * @param CommunicationFactory   $communicationFactory
     * @param EntityManagerInterface $entityManager
     * @param Dispatcher             $communicationDispatcher
     * @param CampaignRepository     $campaignRepository
     */
    public function __construct(
        CampaignFactory $campaignFactory,
        CommunicationFactory $communicationFactory,
        EntityManagerInterface $entityManager,
        Dispatcher $communicationDispatcher,
        CampaignRepository $campaignRepository
    ) {
        $this->campaignFactory         = $campaignFactory;
        $this->communicationFactory    = $communicationFactory;
        $this->entityManager           = $entityManager;
        $this->communicationDispatcher = $communicationDispatcher;
        $this->campaignRepository      = $campaignRepository;
    }

    /**
     * Launches a campaign by creating a new one and sending an initial communication to a list of volunteers.
     *
     * @param string     $label
     * @param string     $color
     * @param Collection $volunteers
     * @param string     $message
     * @param string[]   $choiceValues
     * @param bool       $geoLocation
     * @param string     $type
     * @param bool       $multipleAnswer
     * @param string     $subject
     *
     * @return Campaign
     */
    public function launchNewCampaign(
        string $label,
        string $color,
        $volunteers,
        string $message,
        array $choiceValues,
        bool $geoLocation,
        string $type,
        bool $multipleAnswer,
        ?string $subject)
    {
        // Create the campaign with an initial communication
        $communication = $this->communicationFactory->create($message, $volunteers, $choiceValues, $geoLocation, $type, $multipleAnswer, $subject);
        $campaign      = $this->campaignFactory->create($label, $color, $communication);

        $this->entityManager->persist($campaign);
        $this->entityManager->flush();

        // Dispatch the communication
        $this->communicationDispatcher->dispatch($communication);

        return $campaign->getId();
    }

    /**
     * @param Campaign                      $campaign
     * @param \App\Form\Model\Communication $communication
     *
     * @return Campaign
     * @throws \LogicException
     */
    public function createNewCommunication(Campaign $campaign, \App\Form\Model\Communication $communicationModel)
    {
        if (!$campaign->isActive()) {
            throw new \LogicException('Cannot dispatch a new communication on a finished campaign');
        }

        // Create a new communication and attach it to the campaign
        $communicationEntity = $this->createCommunicationEntity($communicationModel);
        $campaign->addCommunication($communicationEntity);

        $this->entityManager->persist($campaign);
        $this->entityManager->flush();

        // Dispatch the communication
        $this->communicationDispatcher->dispatch($communicationEntity);

        return $campaign;
    }

    /**
     * @param \App\Form\Model\Communication $communication
     *
     * @return \App\Entity\Communication
     */
    public function createCommunicationEntity(\App\Form\Model\Communication $communication)
    {
        $volunteers     = $communication->volunteers;
        $message        = $communication->message;
        $choiceValues   = $communication->answers;
        $geoLocation    = $communication->geoLocation;
        $type           = $communication->type;
        $multipleAnswer = $communication->multipleAnswer;
        $subject        = $communication->subject;

        return $this->communicationFactory->create($message, $volunteers, $choiceValues, $geoLocation, $type, $multipleAnswer, $subject);
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
}