<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Bulletin;
use App\Entity\Grade;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Grade>
 *
 * @method Grade|null find($id, $lockMode = null, $lockVersion = null)
 * @method Grade|null findOneBy(array $criteria, array $orderBy = null)
 * @method Grade[]    findAll()
 * @method Grade[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GradeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Grade::class);
    }

    /**
     * @return Grade[] Returns grades for a specific bulletin
     */
    public function findByBulletin(Bulletin $bulletin): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.bulletin = :bulletin')
            ->setParameter('bulletin', $bulletin)
            ->orderBy('g.subject', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns the weighted average for a bulletin.
     */
    public function calculateWeightedAverage(Bulletin $bulletin): ?float
    {
        $result = $this->createQueryBuilder('g')
            ->select('SUM(g.grade * g.coefficient) as weightedSum, SUM(g.coefficient) as totalCoeff')
            ->andWhere('g.bulletin = :bulletin')
            ->setParameter('bulletin', $bulletin)
            ->getQuery()
            ->getSingleResult();

        if (!$result['totalCoeff'] || (float) $result['totalCoeff'] === 0.0) {
            return null;
        }

        return round((float) $result['weightedSum'] / (float) $result['totalCoeff'], 2);
    }
}
