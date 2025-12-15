<?php
namespace App\Repository;

use App\Entity\CensoredText;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CensoredTextRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CensoredText::class);
    }

    public function findByTextHash(string $hash): ?CensoredText
    {
        return $this->findOneBy(['textHash' => $hash]);
    }

    public function save(CensoredText $censoredText): void
    {
        $this->getEntityManager()->persist($censoredText);
        $this->getEntityManager()->flush();
    }

    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('ct');
        
        return [
            'total' => (int) $qb->select('COUNT(ct.id)')
                ->getQuery()
                ->getSingleScalarResult(),
            
            'withBadWords' => (int) $qb->select('COUNT(ct.id)')
                ->where('ct.hasBadWords = :true')
                ->setParameter('true', true)
                ->getQuery()
                ->getSingleScalarResult(),
            
            'totalUsage' => (int) $qb->select('SUM(ct.usageCount)')
                ->getQuery()
                ->getSingleScalarResult(),
        ];
    }

    public function cleanOldRecords(): int
    {
        $qb = $this->createQueryBuilder('ct');
        
        return $qb->delete()
            ->where('ct.lastUsedAt < :date')
            ->setParameter('date', new \DateTime('-90 days'))
            ->getQuery()
            ->execute();
    }
}