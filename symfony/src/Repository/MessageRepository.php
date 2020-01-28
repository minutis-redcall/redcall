<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Answer;
use App\Entity\Call;
use App\Entity\Campaign;
use App\Entity\Choice;
use App\Entity\Message;
use App\Entity\Selection;
use App\Entity\Volunteer;
use App\Tools\Random;
use Doctrine\Bundle\DoctrineBundle\Registry;

class MessageRepository extends BaseRepository
{
    const CODE_SIZE = 8;

    public function __construct(Registry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * @param string $phoneNumber
     * @param string $prefix
     *
     * @return Message|null
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getMessageFromPhoneNumber(string $phoneNumber): ?Message
    {
        return $this->createQueryBuilder('m')
                    ->join('m.volunteer', 'v')
                    ->join('m.communication', 'co')
                    ->join('co.campaign', 'ca')
                    ->where('v.phoneNumber = :phoneNumber')
                    ->andWhere('ca.active = true')
                    ->orderBy('m.id', 'DESC')
                    ->setMaxResults(1)
                    ->setParameter('phoneNumber', $phoneNumber)
                    ->getQuery()
                    ->getOneOrNullResult();
    }

    /**
     * @param string $phoneNumber
     * @param string $prefix
     *
     * @return Message|null
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getMessageFromPhoneNumberAndPrefix(string $phoneNumber, string $prefix): ?Message
    {
        return $this->createQueryBuilder('m')
                    ->join('m.volunteer', 'v')
                    ->join('m.communication', 'co')
                    ->join('co.campaign', 'ca')
                    ->where('v.phoneNumber = :phoneNumber')
                    ->setParameter('phoneNumber', $phoneNumber)
                    ->andWhere('m.prefix = :prefix')
                    ->setParameter('prefix', $prefix)
                    ->andWhere('ca.active = true')
                    ->orderBy('m.id', 'DESC')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();
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
    public function generateCode(string $column = 'code'): string
    {
        do {
            $code = Random::generate(self::CODE_SIZE);

            if (null === $this->findOneBy([$column => $code])) {
                break;
            }

        } while (true);

        return $code;
    }

    /**
     * @param Volunteer $volunteer
     * @param string    $prefix
     */
    public function getMessageFromVolunteer(Volunteer $volunteer, string $prefix)
    {
        return $this->createQueryBuilder('m')
                    ->join('m.communication', 'co')
                    ->join('co.campaign', 'ca')
                    ->where('ca.active = true')
                    ->andWhere('m.volunteer = :volunteer')
                    ->andWhere('m.prefix = :prefix')
                    ->setParameter('volunteer', $volunteer)
                    ->setParameter('prefix', $prefix)
                    ->getQuery()
                    ->getOneOrNullResult();
    }

    /**
     * @param array $volunteersTakenPrefixes
     *
     * @return bool
     */
    public function canUsePrefixesForEveryone(array $volunteersTakenPrefixes): bool
    {
        if (!$volunteersTakenPrefixes) {
            return true;
        }

        $qb = $this->createQueryBuilder('m')
                   ->select('COUNT(m.id)')
                   ->join('m.communication', 'co')
                   ->join('co.campaign', 'ca')
                   ->join('m.volunteer', 'v')
                   ->where('ca.active = true')
                   ->andWhere('v.id IN (:volunteerIds)')
                   ->setParameter('volunteerIds', array_keys($volunteersTakenPrefixes));

        // Simulating CASE WHEN THEN END
        $orXs = [];
        foreach ($volunteersTakenPrefixes as $volunteerId => $prefixes) {
            $orXs[] = sprintf('v.id = :v_%d AND m.prefix IN (:p_%d)', $volunteerId, $volunteerId);
            $qb->setParameter(sprintf('v_%d', $volunteerId), $volunteerId);
            $qb->setParameter(sprintf('p_%d', $volunteerId), $prefixes);
        }

        $result = (bool)$qb
            ->andWhere(call_user_func_array([$qb->expr(), 'orX'], $orXs))
            ->getQuery()
            ->getSingleScalarResult();

        return !$result;
    }
}
