<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Answer;
use App\Entity\Call;
use App\Entity\Campaign;
use App\Entity\Choice;
use App\Entity\Message;
use App\Entity\Selection;
use App\Tools\Random;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;

class MessageRepository extends BaseRepository
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

    /**
     * @param string $phoneNumber
     * @param string $prefix
     *
     * @return Message|null
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getMessageFromPhoneNumber(string $phoneNumber, string $prefix)
    {
        return $this->createQueryBuilder('m')
                    ->innerJoin('App:Volunteer', 'v', 'WITH', 'v = m.volunteer')
                    ->where('v.phoneNumber = :from')
                    ->orderBy('m.id', 'DESC')
                    ->setMaxResults(1)
                    ->setParameter('from', $phoneNumber)
                    ->getQuery()
                    ->getOneOrNullResult();
    }

    /**
     * @param Message $message
     * @param string  $body
     * @param bool    $byAdmin
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function addAnswer(Message $message, string $body, bool $byAdmin = false): void
    {
        // Get all valid choices in message
        if ($multipleChoice = $message->getCommunication()->isMultipleAnswer()) {
            $choices = $message->getCommunication()->getAllChoicesInText($body);
        } else {
            $choices = [];
            if ($choice = $message->getCommunication()->getChoiceByCode($body)) {
                $choices[] = $choice;
            }
        }

        if (!$multipleChoice) {
            // If no multiple answers are allowed, clearing up all previous answers
            foreach ($message->getAnswers() as $answer) {
                /* @var Answer $answer */
                $answer->getChoices()->clear();
                $this->_em->persist($answer);
            }
        } else {
            // If mulitple answers allowed, we'll only keep the last duplicate
            foreach ($choices as $choice) {
                if ($answer = $message->getAnswerByChoice($choice)) {
                    $answer->getChoices()->removeElement($choice);
                    $this->_em->persist($answer);
                }
            }
        }

        // Storing the new answer
        $answer = new Answer();
        $message->addAnswser($answer);
        $answer->setMessage($message);
        $answer->setRaw($body);
        $answer->setReceivedAt(new \DateTime());
        $answer->setUnclear($message->getCommunication()->isUnclear($body));

        if ($byAdmin) {
            $answer->setByAdmin($this->tokenStorage->getToken()->getUsername());
        }

        foreach ($choices as $choice) {
            $answer->addChoice($choice);
        }

        $this->_em->persist($answer);
        $this->_em->flush();
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
            if ($answer->getChoices()->removeElement($choice)) {
                $this->_em->persist($answer);
            }
        }

        $this->_em->flush();
    }

    /**
     * @param Message $message
     * @param Choice  $choice
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function toggleAnswer(Message $message, Choice $choice)
    {
        // If choice currently selected, remove it
        if ($answer = $message->getAnswerByChoice($choice)) {
            $answer->getChoices()->removeElement($choice);
            $this->_em->flush();

            return;
        }

        $this->addAnswer($message, $choice->getCode(), true);
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
