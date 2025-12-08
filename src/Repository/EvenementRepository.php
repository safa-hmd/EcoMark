<?php

namespace App\Repository;

use App\Entity\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Evenement>
 */
class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }

    //    /**
    //     * @return Evenement[] Returns an array of Evenement objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('e.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Evenement
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

public function searchEvents(string $search = '')
{
    $qb = $this->createQueryBuilder('e')
               ->orderBy('e.dateEvent', 'ASC');

    if (!empty($search)) {
        $qb->andWhere('e.titre LIKE :s OR e.description LIKE :s OR e.lieu LIKE :s')
           ->setParameter('s', '%' . $search . '%');
    }

    $evenements = $qb->getQuery()->getResult();

    // Tri pondéré par proximité de date
    usort($evenements, function($a, $b) {
        $diffA = $a->getDateEvent()->diff(new \DateTime())->days;
        $diffB = $b->getDateEvent()->diff(new \DateTime())->days;
        return $diffA <=> $diffB;
    });

    return $evenements;
}

}
