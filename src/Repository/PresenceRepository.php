<?php

namespace App\Repository;

use App\Entity\Presence;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Presence|null find($id, $lockMode = null, $lockVersion = null)
 * @method Presence|null findOneBy(array $criteria, array $orderBy = null)
 * @method Presence[]    findAll()
 * @method Presence[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PresenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Presence::class);
    }

	/**
	 * Get a presence entity from a specific date.
	 * @param string $date - A DateTime formated to "y-m-d".
	 * @return Presence|null
	 * @throws NonUniqueResultException
	 */
    public function findOneByDate(string $date): ?Presence
    {
        return $this->createQueryBuilder("s")
            ->where("s.date = :val")
            ->setParameter("val", "$date")
            ->getQuery()
            ->getOneOrNullResult();
    }
}
