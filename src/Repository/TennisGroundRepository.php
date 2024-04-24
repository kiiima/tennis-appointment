<?php

namespace App\Repository;

use App\Entity\TennisGround;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TennisGround>
 *
 * @method TennisGround|null find($id, $lockMode = null, $lockVersion = null)
 * @method TennisGround|null findOneBy(array $criteria, array $orderBy = null)
 * @method TennisGround[]    findAll()
 * @method TennisGround[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TennisGroundRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TennisGround::class);
    }


    public function add(TennisGround $ground, bool $flush = false)
    {
        $this->getEntityManager()->persist($ground);

        if($flush)
        {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TennisGround $ground, bool $flush = false)
    {
        $this->getEntityManager()->remove($ground);

        if($flush)
        {
            $this->getEntityManager()->flush();
        }
    }

    //    /**
    //     * @return TennisGround[] Returns an array of TennisGround objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?TennisGround
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
