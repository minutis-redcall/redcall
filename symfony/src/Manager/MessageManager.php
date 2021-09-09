<?php

namespace App\Manager;

use App\Entity\Answer;
use App\Entity\Campaign;
use App\Entity\Choice;
use App\Entity\Message;
use App\Entity\Volunteer;
use App\Repository\MessageRepository;
use App\Tools\Random;
use DateTime;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class MessageManager
{
    const DEPLOY_GRACE = 120; /* 2 mins */

    /**
     * @var AnswerManager
     */
    private $answerManager;

    /**
     * @var OperationManager
     */
    private $operationManager;

    /**
     * @var MessageRepository
     */
    private $messageRepository;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(AnswerManager $answerManager,
        OperationManager $operationManager,
        MessageRepository $messageRepository,
        TokenStorageInterface $tokenStorage)
    {
        $this->answerManager     = $answerManager;
        $this->operationManager  = $operationManager;
        $this->messageRepository = $messageRepository;
        $this->tokenStorage      = $tokenStorage;
    }

    public function generateCodes(int $numberOfCodes)
    {
        $codes = [];
        do {
            while ($numberOfCodes != count($codes)) {
                $code    = Random::generate(MessageRepository::CODE_SIZE);
                $codes[] = $code;
            }

            foreach ($this->messageRepository->findUsedCodes($codes) as $alreadyUsed) {
                unset($codes[$alreadyUsed]);
            }
        } while ($numberOfCodes != count($codes));

        return array_values($codes);
    }

    public function find(int $messageId) : ?Message
    {
        return $this->messageRepository->find($messageId);
    }

    public function getNumberOfSentMessages(Campaign $campaign) : int
    {
        return $this->messageRepository->getNumberOfSentMessages($campaign);
    }

    public function generatePrefixes(array $volunteers) : array
    {
        $usedPrefixes = $this->messageRepository->getUsedPrefixes($volunteers);

        $prefixes = [];
        foreach ($volunteers as $volunteer) {
            /** @var Volunteer $volunteer */
            for ($prefix = 'A'; in_array($prefix, $usedPrefixes[$volunteer->getId()] ?? []); $prefix++) {
                // Hack: A+1=B, Z+1=AA, ...
                // Let me be happy to find a use case for this shit 😅
            }
            $prefixes[$volunteer->getId()] = $prefix;
        }

        return $prefixes;
    }

    public function handleAnswer(string $phoneNumber, string $body) : ?int
    {
        // Replaces "A 2" by "A2"
        $body = preg_replace('/([a-z]+)\s*(\d+)/ui', '${1}${2}', $body);

        // In case of multiple calls, we should handle the "A1 B2" body case.
        $messages = [];
        foreach (explode(' ', $body) as $word) {
            $matches = [];
            preg_match('/^([a-zA-Z]+)(\d+)/', $word, $matches);
            if (3 === count($matches)) {
                $message = $this->getMessageFromPhoneNumber($phoneNumber, $word);
                if ($message && !array_key_exists($message->getId(), $messages)) {
                    $messages[$message->getId()] = $message;
                }
            }
        }

        // Answer is invalid, we seek for latest active campaign for the phone number
        if (!$messages) {
            $message = $this->getMessageFromPhoneNumber($phoneNumber, $body);
            if ($message) {
                $messages[] = $message;
            }
        }

        // A better way would be to add a @ManyToMany on Answer<->Message entities,
        // but answers are currently tied too much on their communications.
        foreach ($messages as $message) {
            $this->addAnswer($message, $body);
        }

        /** @var Message|null $message */
        $message = null;
        if ($messages) {
            $message = reset($messages);

            $this->answerManager->handleSpecialAnswers($message, $body);
        }

        // An unknown number sent us a message
        if (!$message) {
            return null;
        }

        return $message->getId();
    }

    public function getMessageFromPhoneNumber(string $phoneNumber, ?string $body = null) : ?Message
    {
        if ($body) {
            $matches = [];
            preg_match('/^([a-zA-Z]+)(\d+)/', $body, $matches);

            // Prefix not found, getting the latest message sent to volunteer on active campaigns
            if (3 === count($matches)) {
                $prefix = strtoupper($matches[1]);

                $message = $this->messageRepository->getMessageFromPhoneNumberAndPrefix($phoneNumber, $prefix);

                if ($message) {
                    return $message;
                }
            }
        }

        return $this->messageRepository->getMessageFromPhoneNumber($phoneNumber);
    }

    public function addAnswer(Message $message, string $body, bool $byAdmin = false) : void
    {
        $choices = [];
        if (0 !== count($message->getCommunication()->getChoices())) {
            // Get all valid choices in message
            if ($multipleChoice = $message->getCommunication()->isMultipleAnswer()) {
                $choices = $message->getCommunication()->getAllChoicesInText($message->getPrefix(), $body);
            } else {
                $choices = [];
                if ($choice = $message->getCommunication()->getChoiceByCode($message->getPrefix(), $body)) {
                    $choices[] = $choice;
                }
            }

            if (!$multipleChoice) {
                // If no multiple answers are allowed, clearing up all previous answers
                $this->answerManager->clearAnswers($message);
            } else {
                // If mulitple answers allowed, we'll only keep the last duplicate
                $this->answerManager->clearChoices($message, $choices);
            }
        }

        // Storing the new answer
        $answer = new Answer();
        $answer->setMessage($message);
        $answer->setRaw($body);
        $answer->setReceivedAt(new DateTime());
        $answer->setUnclear($message->getCommunication()->isUnclear($message->getPrefix(), $body));

        if ($byAdmin) {
            $answer->setByAdmin($this->tokenStorage->getToken()->getUsername());
        }

        foreach ($choices as $choice) {
            $answer->addChoice($choice);
        }

        $message->addAnswser($answer);
        $message->setUpdatedAt(new \DateTime());

        $this->answerManager->save($answer);

        // Handling resource creation / deletion
        if ($message->shouldAddMinutisResource()) {
            $this->operationManager->addResourceToOperation($message);
        } elseif ($message->shouldRemoveMinutisResource()) {
            $this->operationManager->removeResourceFromOperation($message);
        }

        $this->messageRepository->save($message);
    }

    public function toggleAnswer(Message $message, Choice $choice)
    {
        // If choice currently selected, remove it
        $removed = false;
        while ($answer = $message->getAnswerByChoice($choice)) {
            $answer->getChoices()->removeElement($choice);
            $this->answerManager->save($answer);

            if (!$removed && $message->shouldRemoveMinutisResource()) {
                $this->operationManager->removeResourceFromOperation($message);
            }

            $removed = true;
        }

        if ($removed) {
            $this->messageRepository->save($message);

            return;
        }

        $this->addAnswer($message, sprintf('%s%d', $message->getPrefix(), $choice->getCode()), true);
    }

    public function cancelAnswerByChoice(Message $message, Choice $choice) : void
    {
        $this->messageRepository->cancelAnswerByChoice($message, $choice);
    }

    public function canUsePrefixesForEveryone(array $volunteersTakenPrefixes) : bool
    {
        return $this->messageRepository->canUsePrefixesForEveryone($volunteersTakenPrefixes);
    }

    public function save(Message $message)
    {
        $this->messageRepository->save($message);
    }

    public function updateMessageStatus(Message $message)
    {
        $this->messageRepository->updateMessageStatus($message);
    }

    /**
     * Returns true whether is it possible to deploy, if
     * last message was sent less than N seconds ago,
     * we consider that the activity is too high.
     *
     * This method is subject to race conditions, if
     * a user launches a trigger during the deployment
     * time.
     *
     * @return int
     */
    public function getDeployGreenlight() : int
    {
        /** @var Message $message */
        $message = $this->messageRepository->getLatestMessageUpdated();

        if (!$message) {
            return true;
        }

        $diff = time() - $message->getUpdatedAt()->getTimestamp();
        if ($diff > self::DEPLOY_GRACE) {
            return 0;
        }

        return self::DEPLOY_GRACE - $diff;
    }

    /**
     * @param Volunteer $volunteer
     *
     * @return Message[]
     */
    public function getActiveMessagesForVolunteer(Volunteer $volunteer) : array
    {
        return $this->messageRepository->getActiveMessagesForVolunteer($volunteer);
    }
}