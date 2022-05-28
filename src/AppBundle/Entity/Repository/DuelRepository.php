<?php

namespace AppBundle\Entity\Repository;

use AppBundle\Entity\Duel;
use AppBundle\Entity\Quiz;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * DuelRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class DuelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Duel::class);
    }

    public function getCountDuelsForQuiz(Quiz $quiz)
    {
        $qb = $this->createQueryBuilder('d');
        $qb->select('count(d.id)')
            ->where('d.quiz = :quiz')
            ->setParameter(':quiz',$quiz);

        return (int) $qb->getQuery()
            ->getSingleScalarResult();

    }

    public function customQueryPessimistic($duelId)
    {
        $qb = $this->createQueryBuilder('d');
        $qb->select('d');
        $qb->where($qb->expr()->eq('d.id', $duelId));
        $query = $qb->getQuery();
        $query->setLockMode(\Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE);

        return $query->getOneOrNullResult();
    }

    public function customQueryPessimistic2()
    {
        $qb = $this->createQueryBuilder('d');
        $qb->select('d')
            ->where('d.user2 is null')
            ->orderBy('d.id')
            ->setMaxResults(1);
        $query = $qb->getQuery();
        $query->setLockMode(\Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE);

        return $query->getOneOrNullResult();
    }
}
