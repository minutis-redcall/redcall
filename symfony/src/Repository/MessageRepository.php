<?php

namespace App\Repository;

use App\Entity\Answer;
use App\Entity\Call;
use App\Entity\Campaign;
use App\Entity\Choice;
use App\Entity\Message;
use App\Services\Random;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;

class MessageRepository extends ServiceEntityRepository
{
    const CODE_SIZE = 8;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    public function __construct(RegistryInterface $registry,
        TranslatorInterface $translator,
        TokenStorageInterface $tokenStorage)
    {
        parent::__construct($registry, Message::class);

        $this->translator   = $translator;
        $this->tokenStorage = $tokenStorage;
    }

    public function getLastMessageSentToPhone($phoneNumber)
    {
        $stmt = $this->createQueryBuilder('m')
                     ->innerJoin('App:Volunteer', 'v', 'WITH', 'v = m.volunteer')
                     ->where('v.phoneNumber = :from')
                     ->orderBy('m.id', 'DESC')
                     ->setMaxResults(1)
                     ->setParameter('from', $phoneNumber);

        return $stmt->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Message     $message
     * @param string      $body
     * @param Choice|null $forcedChoice
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function addAnswer(Message $message, string $body, ?Choice $forcedChoice = null): void
    {
        // Get all valid choices in message
        if ($multipleChoice = $message->getCommunication()->isMultipleAnswer()) {
            $choices = $message->getCommunication()->getAllChoicesInText($body);
        } else {
            $choices = [$message->getCommunication()->getChoiceByLabelOrCode($body)];
        }

        // Answer ticked manually
        if ($forcedChoice) {
            $choices = [$forcedChoice];
        }

        // Invalid answer
        if (!$choices) {
            $choices[] = null;
        }

        if (!$multipleChoice) {
            // If no multiple answers are allowed, clearing up all previous answers
            foreach ($message->getAnswers() as $answer) {
                if ($answer->getChoice()) {
                    $answer->setChoice(null);
                    $this->_em->persist($answer);
                }
            }
        }

        foreach ($choices as $choice) {
            if ($multipleChoice) {
                // If multiple answers allowed, we'll only keep the last duplicate
                if ($choice && $answer = $message->getAnswerByChoice($choice)) {
                    $answer->setChoice(null);
                    $this->_em->persist($answer);
                }
            }

            $answer = new Answer();

            $answer->setMessage($message);
            $message->addAnswser($answer);

            $answer->setRaw($body);
            $answer->setReceivedAt(new \DateTime());

            $answer->setChoice($choice);

            $this->_em->persist($answer);
            $this->_em->flush();
        }
    }

    /**
     * @param Message $message
     * @param Choice  $choice
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function cancelAnswerByChoice(Message $message, Choice $choice): void
    {
        foreach ($message->getAnswers() as $answer) {
            /* @var Answer $answer */
            if ($answer->isChoice($choice)) {
                $answer->setChoice(null);
                $answer->setUpdatedAt(new \DateTime());

                $this->_em->persist($answer);
            }
        }

        $this->_em->flush();
    }

    /**
     * Method used when only 1 answer is allowed.
     *
     * @param Message $message
     * @param Choice  $choice
     */
    public function changeAnswer(Message $message, Choice $choice)
    {
        $lastAnswer = $message->getLastAnswer();

        // Last answer already removed previously
        if ($lastAnswer && $lastAnswer->getChoice() === null) {
            $lastAnswer = null;
        }

        // Deletes the last answer
        $lastId = $lastAnswer ? $lastAnswer->getChoice()->getId() : null;
        if ($lastId) {
            $lastAnswer->setChoice(null);

            $body = $this->translator->trans('campaign_status.answers.changed_by', [
                '%username%' => $this->tokenStorage->getToken()->getUsername(),
            ]);

            $lastAnswer->setRaw($lastAnswer->getRaw().' '.$body);
            $lastAnswer->setUpdatedAt((new \DateTime())->sub(new \DateInterval('PT1S')));
        }

        // If choice is different from last answer, add it (otherwise we would
        // add the answer we just removed)
        if (!$lastId || $lastId && $choice->getId() !== $lastId) {

            if ($lastId) {
                sleep(1); // makes this answer more recent
            }

            $body = $choice->getCode().' '.$this->translator->trans('campaign_status.answers.added_by', [
                    '%username%' => $this->tokenStorage->getToken()->getUsername(),
                ]);

            $this->addAnswer($message, $body, $choice);
        }

        $this->_em->flush();
    }

    /**
     * Method used when multiple answers are allowed
     *
     * @param Message $message
     * @param Choice  $choice
     */
    public function toggleAnswer(Message $message, Choice $choice)
    {
        $hasAnswer = false;

        foreach ($message->getAnswers() as $answer) {
            if ($answer->getChoice() && $answer->getChoice()->getId() == $choice->getId()) {
                $hasAnswer = true;
                $answer->setChoice(null);
                $body = $this->translator->trans('campaign_status.answers.changed_by', [
                    '%username%' => $this->tokenStorage->getToken()->getUsername(),
                ]);
                $answer->setRaw($answer->getRaw().' '.$body);
            }
        }

        if (!$hasAnswer) {
            $body = $choice->getCode().' '.$this->translator->trans('campaign_status.answers.added_by', [
                    '%username%' => $this->tokenStorage->getToken()->getUsername(),
                ]);
            $this->addAnswer($message, $body, $choice);
        }

        $this->_em->flush();
    }

    /**
     * @param int $messageId
     *
     * @return Message|null
     */
    public function findOneByIdNoCache(int $messageId): ?Message
    {
        return $this->createQueryBuilder('m')
                    ->where('m.id = :id')
                    ->setParameter('id', $messageId)
                    ->getQuery()
                    ->useResultCache(false)
                    ->getOneOrNullResult();
    }

    /**
     * @param Message $message
     *
     * @return Message|null
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     */
    public function refresh(Message $message): Message
    {
        $this->_em->clear();

        return $this->findOneByIdNoCache($message->getId());
    }

    /**
     * @return string
     */
    public function generateWebCode(): string
    {
        return $this->generateCode('webCode');
    }

    /**
     * @param Campaign $campaign
     *
     * @return int
     */
    public function getNumberOfSentMessages(Campaign $campaign): int
    {
        return $this->createQueryBuilder('m')
                    ->select('COUNT(m.id)')
                    ->join('m.communication', 'co')
                    ->join('co.campaign', 'ca')
                    ->where('ca.id = :campaignId')
                    ->andWhere('m.messageId IS NOT NULL')
                    ->setParameter('campaignId', $campaign->getId())
                    ->getQuery()
                    ->useResultCache(false)
                    ->getSingleScalarResult();
    }

    /**
     * Infinite loop risk?
     * POW(62, 8) = 218 340 105 584 896
     * we're safe.
     */
    private function generateCode(string $column): string
    {
        do {

            $code = Random::generate(self::CODE_SIZE);

            if (null === $this->findOneBy([$column => $code])) {
                break;
            }

        } while (true);

        return $code;
    }
}
