<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }


public function countClients(): int
{
    return (int) $this->createQueryBuilder('u')
        ->select('COUNT(u.id)')
        ->andWhere('u.roles LIKE :role')
        ->setParameter('role', '%ROLE_CLIENT%')
        ->getQuery()
        ->getSingleScalarResult();
}

public function countVendeurs(): int
{
    return (int) $this->createQueryBuilder('u')
        ->select('COUNT(u.id)')
        ->andWhere('u.roles LIKE :role')
        ->setParameter('role', '%ROLE_VENDEUR%')
        ->getQuery()
        ->getSingleScalarResult();
}

public function findByClientRole(string $role): array
{
    return $this->createQueryBuilder('u')
        ->andWhere('u.roles LIKE :role')
        ->setParameter('role', '%ROLE_CLIENT%')
        ->getQuery()
        ->getResult();
}
public function findByVendeurRole(string $role): array
{
    return $this->createQueryBuilder('u')
        ->andWhere('u.roles LIKE :role')
        ->setParameter('role', '%ROLE_VENDEUR%')
        ->getQuery()
        ->getResult();

}

public function searchClients(string $term = ''): array
{
    $qb = $this->createQueryBuilder('u')
        ->andWhere('u.roles LIKE :role')
        ->setParameter('role', '%ROLE_CLIENT%');

    if ($term !== '') {
        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->like('u.nom', ':term'),
                $qb->expr()->like('u.prenom', ':term'),
                $qb->expr()->like('u.email', ':term'),
                $qb->expr()->like('u.telephone', ':term'),
                $qb->expr()->like('u.adresse', ':term')
            )
        )
        ->setParameter('term', '%'.$term.'%');
    }

    return $qb->getQuery()->getResult();
}

public function searchVendeurs(string $term = ''): array
{
    $qb = $this->createQueryBuilder('u')
        ->andWhere('u.roles LIKE :role')
        ->setParameter('role', '%ROLE_VENDEUR%');

    if ($term !== '') {
        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->like('u.nom', ':term'),
                $qb->expr()->like('u.prenom', ':term'),
                $qb->expr()->like('u.email', ':term'),
                $qb->expr()->like('u.telephone', ':term'),
                $qb->expr()->like('u.adresse', ':term')
            )
        )
        ->setParameter('term', '%'.$term.'%');
    }

    return $qb->getQuery()->getResult();
}




//Détecter si un utilisateur est actif ou inactif
public function findInactiveUsers(int $minutes = 10): array
{
    $date = new \DateTime();
    $date->modify("-{$minutes} minutes");

    return $this->createQueryBuilder('u')
        ->where('u.lastActivity IS NULL OR u.lastActivity < :date')
        ->setParameter('date', $date)
        ->getQuery()
        ->getResult();
}





/**
 * Statistiques 
 */

public function getSimpleStatistics(): array
{
    return [
        'total_users' => $this->count([]),
        'clients' => $this->countClients(),
        'vendeurs' => $this->countVendeurs(),
        'by_role' => $this->getUsersByRole()
    ];
}
/**
 * Distribution des utilisateurs par rôle
 */
public function getUsersByRole(): array
{
    $results = $this->createQueryBuilder('u')
        ->select('
            SUM(CASE WHEN u.roles LIKE :roleClient THEN 1 ELSE 0 END) as clients,
            SUM(CASE WHEN u.roles LIKE :roleVendeur THEN 1 ELSE 0 END) as vendeurs,
            SUM(CASE WHEN u.roles LIKE :roleAdmin THEN 1 ELSE 0 END) as admins
        ')
        ->setParameter('roleClient', '%ROLE_CLIENT%')
        ->setParameter('roleVendeur', '%ROLE_VENDEUR%')
        ->setParameter('roleAdmin', '%ROLE_ADMIN%')
        ->getQuery()
        ->getSingleResult();
        
    return [
        'clients' => (int) ($results['clients'] ?? 0),
        'vendeurs' => (int) ($results['vendeurs'] ?? 0),
        'admins' => (int) ($results['admins'] ?? 0)
    ];
}

/**
 * Compte les utilisateurs actifs
 */
public function getActiveUsersCount(\DateTimeInterface $since): int
{
    return (int) $this->createQueryBuilder('u')
        ->select('COUNT(u.id)')
        ->where('u.lastActivity >= :since')
        ->setParameter('since', $since)
        ->getQuery()
        ->getSingleScalarResult();
}
}
