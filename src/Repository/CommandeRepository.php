<?php

namespace App\Repository;

use App\Entity\Commande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    // 📅 Nombre de commandes par mois
    public function countByMonth()
    {
        return $this->createQueryBuilder('c')
            ->select("SUBSTRING(c.dateCommande, 6, 2) AS month, COUNT(c.id) AS count")
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // 💶 Revenus par mois
    public function revenueByMonth()
    {
        return $this->createQueryBuilder('c')
            ->select("SUBSTRING(c.dateCommande, 6, 2) AS month, SUM(c.montantTotal) AS total")
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // 💳 NOUVEAU — Répartition par méthode de paiement
    public function countByMethodePaiement()
    {
        return $this->createQueryBuilder('c')
            ->select("c.methodePaiement AS methode, COUNT(c.id) AS total")
            ->groupBy('c.methodePaiement')
            ->getQuery()
            ->getResult();
    }
}
