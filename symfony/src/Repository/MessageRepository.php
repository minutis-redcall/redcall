<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Answer;
use App\Entity\Call;
use App\Entity\Campaign;
use App\Entity\Choice;
use App\Entity\Message;
use App\Entity\Selection;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\NoResultException;

class MessageRepository extends BaseRepository
{
    const CODE_SIZE = 8;

    public function __construct(Registry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function getMessageFromPhoneNumber(string $phoneNumber) : ?Message
    {
        return $this->createQueryBuilder('m')
                    ->join('m.volunteer', 'v')
                    ->join('m.communication', 'co')
                    ->join('co.campaign', 'ca')
                    ->join('v.phones', 'p')
                    ->where('p.e164 = :phoneNumber')
                    ->andWhere('ca.active = true')
                    ->orderBy('m.id', 'DESC')
                    ->setMaxResults(1)
                    ->setParameter('phoneNumber', $phoneNumber)
                    ->getQuery()
                    ->getOneOrNullResult();
    }

    public function getMessageFromPhoneNumberAndPrefix(string $phoneNumber, string $prefix) : ?Message
    {
        return $this->createQueryBuilder('m')
                    ->join('m.volunteer', 'v')
                    ->join('m.communication', 'co')
                    ->join('co.campaign', 'ca')
                    ->join('v.phones', 'p')
                    ->where('p.e164 = :phoneNumber')
                    ->setParameter('phoneNumber', $phoneNumber)
                    ->andWhere('m.prefix = :prefix')
                    ->setParameter('prefix', $prefix)
                    ->andWhere('ca.active = true')
                    ->orderBy('m.id', 'DESC')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();
    }

    public function cancelAnswerByChoice(Message $message, Choice $choice) : void
    {
        foreach ($message->getAnswers() as $answer) {
            /* @var Answer $answer */
            if ($answer->getChoices()->removeElement($choice)) {
                $this->_em->persist($answer);
            }
        }

        $this->_em->flush();
    }

    public function findOneByIdNoCache(int $messageId) : ?Message
    {
        return $this->createQueryBuilder('m')
                    ->where('m.id = :id')
                    ->setParameter('id', $messageId)
                    ->getQuery()
                    ->useResultCache(false)
                    ->getOneOrNullResult();
    }

    public function refresh(Message $message) : Message
    {
        $this->_em->clear();

        return $this->findOneByIdNoCache($message->getId());
    }

    public function getNumberOfSentMessages(Campaign $campaign) : int
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

    public function findUsedCodes(array $codes)
    {
        $rows = $this->createQueryBuilder('m')
                     ->select('m.code')
                     ->where('m.code IN (:codes)')
                     ->setParameter('codes', $codes)
                     ->getQuery()
                     ->getArrayResult();

        return array_column($rows, 'code');
    }

    public function getUsedPrefixes(array $volunteers) : array
    {
        $rows = $this->createQueryBuilder('m')
                     ->select('v.id, m.prefix')
                     ->join('m.communication', 'co')
                     ->join('co.campaign', 'ca')
                     ->join('m.volunteer', 'v')
                     ->where('ca.active = true')
                     ->andWhere('v.id IN (:volunteers)')
                     ->setParameter('volunteers', $volunteers)
                     ->getQuery()
                     ->getArrayResult();

        $prefixes = [];
        foreach ($rows as $row) {
            if (!array_key_exists($row['id'], $prefixes)) {
                $prefixes[$row['id']] = [];
            }
            $prefixes[$row['id']][] = $row['prefix'];
        }

        return $prefixes;
    }

    public function canUsePrefixesForEveryone(array $volunteersTakenPrefixes) : bool
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

        $result = (bool) $qb
            ->andWhere(call_user_func_array([$qb->expr(), 'orX'], $orXs))
            ->getQuery()
            ->getSingleScalarResult();

        return !$result;
    }

    public function getLatestMessageUpdated() : ?Message
    {
        try {
            return $this->createQueryBuilder('m')
                        ->orderBy('m.updatedAt', 'DESC')
                        ->setMaxResults(1)
                        ->getQuery()
                        ->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        }
    }
}
