<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Bulletin;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Bulletin>
 *
 * @method Bulletin|null find($id, $lockMode = null, $lockVersion = null)
 * @method Bulletin|null findOneBy(array $criteria, array $orderBy = null)
 * @method Bulletin[]    findAll()
 * @method Bulletin[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BulletinRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bulletin::class);
    }

    /**
     * @return Bulletin[] Returns bulletins for a specific student
     */
    public function findByStudent(User $student): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.student = :student')
            ->setParameter('student', $student)
            ->orderBy('b.academicYear', 'DESC')
            ->addOrderBy('b.semester', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Bulletin[] Returns bulletins for a given academic year
     */
    public function findByAcademicYear(string $academicYear): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.academicYear = :year')
            ->setParameter('year', $academicYear)
            ->orderBy('b.semester', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Bulletin[] Returns bulletins pending validation
     */
    public function findPendingValidation(): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.validatedBy IS NULL')
            ->orderBy('b.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
