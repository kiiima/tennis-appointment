<?php

namespace App\Repository;

use App\Entity\WorkingTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkingTime>
 *
 * @method WorkingTime|null find($id, $lockMode = null, $lockVersion = null)
 * @method WorkingTime|null findOneBy(array $criteria, array $orderBy = null)
 * @method WorkingTime[]    findAll()
 * @method WorkingTime[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WorkingTimeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkingTime::class);
    }

    public function add(WorkingTime $shift, bool $flush = false)
    {
        $this->getEntityManager()->persist($shift);

        if($flush)
        {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(WorkingTime $shift, bool $flush = false)
    {
        $this->getEntityManager()->remove($shift);     
        
        if($flush)
        {
            $this->getEntityManager()->flush();
        }
    }


    //    /**
    //     * @return WorkingTime[] Returns an array of WorkingTime objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('w')
    //            ->andWhere('w.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('w.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?WorkingTime
    //    {
    //        return $this->createQueryBuilder('w')
    //            ->andWhere('w.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
