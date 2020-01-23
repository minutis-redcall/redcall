<?php

namespace App\Manager;

use App\Communication\Processor\ProcessorInterface;
use App\Entity\Campaign;
use App\Entity\Choice;
use App\Entity\Communication;
use App\Entity\Communication as CommunicationEntity;
use App\Entity\Message;
use App\Form\Model\Communication as CommunicationModel;
use App\Repository\CommunicationRepository;

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
     * @param MessageManager          $messageManager
     * @param CommunicationRepository $communicationRepository
     * @param ProcessorInterface      $processor
     */
    public function __construct(MessageManager $messageManager,
        CommunicationRepository $communicationRepository,
        ProcessorInterface $processor)
    {
        $this->messageManager          = $messageManager;
        $this->communicationRepository = $communicationRepository;
        $this->processor               = $processor;
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
     * @return CommunicationEntity|null
     */
    public function find(int $communicationId): ?CommunicationEntity
    {
        return $this->communicationRepository->find($communicationId);
    }

    /**
     * @param Campaign           $campaign
     * @param CommunicationModel $communicationModel
     *
     * @return CommunicationEntity
     * @throws \Exception
     *
     */
    public function launchNewCommunication(Campaign $campaign,
        CommunicationModel $communicationModel): CommunicationEntity
    {
        $communicationEntity = $this->createCommunication($communicationModel);

        $campaign->addCommunication($communicationEntity);
        foreach ($communicationModel->structures as $structure) {
            $campaign->addStructure($structure);
        }

        $this->campaignManager->save($campaign);

        $this->processor->process($communicationEntity);

        $this->communicationRepository->save($communicationEntity);

        return $communicationEntity;
    }

    /**
     * @param CommunicationModel $communicationModel
     *
     * @return CommunicationEntity
     *
     * @throws \Exception
     */
    public function createCommunication(CommunicationModel $communicationModel): CommunicationEntity
    {
        $communicationEntity = new CommunicationEntity();
        $communicationEntity
            ->setType($communicationModel->type)
            ->setBody($communicationModel->message)
            ->setGeoLocation($communicationModel->geoLocation)
            ->setCreatedAt(new \DateTime())
            ->setMultipleAnswer($communicationModel->multipleAnswer)
            ->setSubject($communicationModel->subject);

        foreach ($communicationModel->volunteers as $volunteer) {
            $message = new Message();

            $message->setWebCode(
                $this->messageManager->generateWebCode()
            );

            $communicationEntity->addMessage($message->setVolunteer($volunteer));
        }

        // The first choice key is always "1"
        $choiceKey = 1;
        foreach (array_unique($communicationModel->answers) as $choiceValue) {
            $choice = new Choice();
            $choice
                ->setCode($choiceKey)
                ->setLabel($choiceValue);

            $communicationEntity->addChoice($choice);
            $choiceKey++;
        }

        return $communicationEntity;
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