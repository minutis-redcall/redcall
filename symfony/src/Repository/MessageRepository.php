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
     * @param Message $message
     * @param string  $body
     */
    public function addAnswer(Message $message, string $body, ?Choice $forcedChoice = null): void
    {
        $answer = new Answer();

        $answer->setMessage($message);
        $message->addAnswser($answer);

        $answer->setRaw($body);
        $answer->setReceivedAt(new \DateTime());

        $answer->setChoice($message->getCommunication()->getChoiceByLabelOrCode($body));
        if ($forcedChoice) {
            $answer->setChoice($forcedChoice);
        }

        $this->_em->persist($answer);
        $this->_em->flush();
    }

    /**
     * @param Message $message
     * @param Choice  $choice
     */
    public function cancelAnswerByChoice(Message $message, Choice $choice): void
    {
        foreach ($message->getAnswers() as $answer) {
            if ($answer->isChoice($choice)) {
                $message->removeAnswer($answer);

                $this->_em->remove($answer);
                $this->_em->flush();
            }
        }
    }

    /**
     * @param Message $message
     * @param Choice  $choice
     */
    public function changeAnswer(Message $message, Choice $choice)
    {
        $lastAnswer = $message->getLastAnswer();
        $lastId = $lastAnswer ? $lastAnswer->getChoice()->getId() : null;

        // Deletes the last answer
        if ($lastAnswer) {
            $lastAnswer->setChoice(null);

            $body = $this->translator->trans('campaign_status.answers.changed_by', [
                '%username%' => $this->tokenStorage->getToken()->getUsername(),
            ]);

            $lastAnswer->setRaw($lastAnswer->getRaw() . ' ' . $body);
        }

        // If choice is different that last answer, add it (otherwise we would
        // add the answer we just removed)
        if ($lastId && $choice->getId() !== $lastId) {
            $body = $choice->getCode() . ' ' . $this->translator->trans('campaign_status.answers.added_by', [
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
     * @return string
     */
    public function generateGeoCode(): string
    {
        return $this->generateCode('geoCode');
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
