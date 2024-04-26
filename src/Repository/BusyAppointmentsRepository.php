<?php

namespace App\Repository;

use App\Entity\BusyAppointments;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BusyAppointments>
 *
 * @method BusyAppointments|null find($id, $lockMode = null, $lockVersion = null)
 * @method BusyAppointments|null findOneBy(array $criteria, array $orderBy = null)
 * @method BusyAppointments[]    findAll()
 * @method BusyAppointments[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BusyAppointmentsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BusyAppointments::class);
    }


    public function add(BusyAppointments $appointment, bool $flush = false)
    {
        $this->getEntityManager()->persist($appointment);

        if($flush)
        {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(BusyAppointments $appointment, bool $flush = false)
    {
        $this->getEntityManager()->remove($appointment);

        if($flush)
        {
            $this->getEntityManager()->flush();
        }
    }

    /** @return BusyAppointments[] */
    public function findByOutOfRange($start_time, $end_time): array
    {
        $query = $this->createQueryBuilder('p') //mi smo u busyAppointment tabeli
                ->where('(p.startTime < :start_time and p.endTime <= :start_time ) or (p.startTime >= :end_time and p.endTime > :end_time)')
                ->setParameter('start_time', $start_time)
                ->setParameter('end_time', $end_time)
                ->getQuery();

        return $query->execute();
    }

    //    /**
    //     * @return BusyAppointments[] Returns an array of BusyAppointments objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('b.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?BusyAppointments
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
