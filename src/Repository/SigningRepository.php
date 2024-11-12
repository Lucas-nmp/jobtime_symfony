<?php

namespace App\Repository;

use App\Entity\Signing;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Signing>
 */
class SigningRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Signing::class);
    }

    public function getTotalHoursWorked($userId, \DateTimeInterface $startDate, \DateTimeInterface $endDate)
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.user = :userId')
            ->andWhere('s.datetime BETWEEN :startDate AND :endDate')
            ->setParameter('userId', $userId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('s.datetime', 'ASC')
            ->getQuery();

        $signings = $qb->getResult();
        $totalSeconds = 0;

        // Calcular el total de tiempo trabajado en segundos
        for ($i = 0; $i < count($signings) - 1; $i += 2) {
            $entry = $signings[$i]->getDatetime();
            $exit = $signings[$i + 1]->getDatetime();

            // Calcular la diferencia en segundos
            $interval = $entry->diff($exit);
            $secondsWorked = ($interval->h * 3600) + ($interval->i * 60) + $interval->s;
            $totalSeconds += $secondsWorked;
        }

        // Convertir el total de segundos a horas, minutos y segundos
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $seconds = $totalSeconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }


    //    /**
    //     * @return Signing[] Returns an array of Signing objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Signing
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
