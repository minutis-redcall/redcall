<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Answer;
use App\Entity\Message;
use App\Entity\Volunteer;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Answer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Answer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Answer[]    findAll()
 * @method Answer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnswerRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Answer::class);
    }

    public function clearAnswers(Message $message)
    {
        foreach ($message->getAnswers() as $answer) {
            /* @var Answer $answer */
            $answer->getChoices()->clear();
            $this->_em->persist($answer);
        }

        $this->_em->flush();
    }

    public function clearChoices(Message $message, array $choices)
    {
        foreach ($choices as $choice) {
            if ($answer = $message->getAnswerByChoice($choice)) {
                $answer->getChoices()->removeElement($choice);
                $this->_em->persist($answer);
            }
        }

        $this->_em->flush();
    }

    public function getSearchQueryBuilder(string $criteria) : QueryBuilder
    {
        $qb = $this->createQueryBuilder('a')
                   ->join('a.message', 'm')
                   ->join('m.volunteer', 'v')
                   ->where('v.enabled = true');

        $exprs = [];
        foreach (explode(' ', $criteria) as $index => $keyword) {
            $exprs[] = $qb->expr()->like('a.raw', sprintf(':keyword_%d', $index));
            $qb->setParameter(sprintf('keyword_%d', $index), sprintf('%%%s%%', $keyword));
        }

        $qb->andWhere(
            call_user_func_array([$qb->expr(), 'orX'], $exprs)
        );

        $qb->orderBy('a.id', 'DESC');

        return $qb;
    }

    public function getVolunteerAnswersQueryBuilder(Volunteer $volunteer) : QueryBuilder
    {
        return $this->createQueryBuilder('a')
                    ->join('a.message', 'm')
                    ->join('m.volunteer', 'v')
                    ->where('v.id = :volunteer_id')
                    ->setParameter('volunteer_id', $volunteer->getId())
                    ->orderBy('a.id', 'DESC');
    }
}
