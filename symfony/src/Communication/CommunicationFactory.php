<?php

namespace App\Communication;

use App\Entity\Choice;
use App\Entity\Communication;
use App\Entity\Message;
use App\Repository\MessageRepository;
use Doctrine\Common\Collections\Collection;

class CommunicationFactory
{
    /**
     * @var MessageRepository
     */
    protected $messageRepository;

    /**
     * CommunicationFactory constructor.
     *
     * @param MessageRepository $messageRepository
     */
    public function __construct(MessageRepository $messageRepository)
    {
        $this->messageRepository = $messageRepository;
    }

    /**
     * @param string     $message
     * @param Collection $volunteers
     * @param string[]   $choiceValues
     * @param bool       $geoLocation
     * @param string     $type
     *
     * @return Communication
     */
    public function create(string $message, $volunteers, array $choiceValues, bool $geoLocation, string $type)
    {
        $communication = new Communication();
        $communication
            ->setType($type)
            ->setStatus(Communication::STATUS_PENDING)
            ->setBody($message)
            ->setGeoLocation($geoLocation)
            ->setCreatedAt(new \DateTime());

        foreach ($volunteers as $volunteer) {
            $message = new Message();

            if (Communication::TYPE_WEB === $type) {
                $message->setWebCode(
                    $this->messageRepository->generateWebCode()
                );
            }

            if ($geoLocation) {
                $message->setGeoCode(
                    $this->messageRepository->generateGeoCode()
                );
            }

            $communication->addMessage($message->setVolunteer($volunteer));
        }

        // The first choice key is always "1"
        $choiceKey = 1;
        foreach ($choiceValues as $choiceValue) {
            $choice = new Choice();
            $choice
                ->setCode($choiceKey)
                ->setLabel($choiceValue);

            $communication->addChoice($choice);
            $choiceKey++;
        }

        return $communication;
    }
}