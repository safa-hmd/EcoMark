<?php

namespace App\Repository;

use App\Entity\Reclamation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reclamation>
 */
class ReclamationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reclamation::class);
    }

      public function searchByAllAttributes(string $search = '', string $date = '')
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.client', 'c')
            ->addSelect('c')
            ->leftJoin('r.yes', 'rep')   // si tu as une entité Reponse
            ->addSelect('rep');

        if (!empty($search)) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('r.objet', ':search'),
                    $qb->expr()->like('r.description', ':search'),
                    $qb->expr()->like('r.statut', ':search'),
                    $qb->expr()->like('c.email', ':search'),
                    $qb->expr()->like('rep.contenu', ':search')
                )
            )
            ->setParameter('search', '%' . $search . '%');
        }

          if (!empty($date)) {
        $dateStart = new \DateTime($date . ' 00:00:00');
        $dateEnd   = new \DateTime($date . ' 23:59:59');

        $qb->andWhere('r.dateCreation BETWEEN :start AND :end')
           ->setParameter('start', $dateStart)
           ->setParameter('end', $dateEnd);
    }

    $qb->orderBy('r.dateCreation', 'DESC');

    return $qb->getQuery()->getResult();
}
public function findByClientQuery($user)
{
    return $this->createQueryBuilder('r')
                ->where('r.client = :user')
                ->setParameter('user', $user)
                ->orderBy('r.dateCreation', 'DESC')
                ->getQuery();
}
}

//    /**
//     * @return Reclamation[] Returns an array of Reclamation objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Reclamation
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

