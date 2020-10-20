<?php

namespace App\Repository;

use App\Entity\Schedule;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Schedule|null find($id, $lockMode = null, $lockVersion = null)
 * @method Schedule|null findOneBy(array $criteria, array $orderBy = null)
 * @method Schedule[]    findAll()
 * @method Schedule[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ScheduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Schedule::class);
    }

	/**
	 * Find a schedule by a time range.
	 * @param DateTime $start - The schedule start time.
	 * @param DateTime $end - The schedule end time.
	 * @return Schedule|null
	 * @throws NonUniqueResultException
	 */
    public function findOneByRange(DateTime $start, DateTime $end): ?Schedule
    {
        return $this->createQueryBuilder("s")
            ->where("s.start = :start")
            ->andWhere("s.end = :end")
            ->setParameter("start", $start->format("H:i:s"))
            ->setParameter("end", $end->format("H:i:s"))
            ->getQuery()
            ->getOneOrNullResult();
    }
}
