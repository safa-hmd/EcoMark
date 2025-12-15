<?php
// src/Repository/ReactionReponseRepository.php
namespace App\Repository;

use App\Entity\ReactionReponse;
use App\Entity\Reponse;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReactionReponse>
 *
 * @method ReactionReponse|null find($id, $lockMode = null, $lockVersion = null)
 * @method ReactionReponse|null findOneBy(array $criteria, array $orderBy = null)
 * @method ReactionReponse[]    findAll()
 * @method ReactionReponse[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReactionReponseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReactionReponse::class);
    }

    /**
     * Récupère la réaction d’un utilisateur sur une réponse
     */
    public function findByUserAndReponse(User $user, Reponse $reponse): ?ReactionReponse
    {
        return $this->findOneBy([
            'user' => $user,
            'reponse' => $reponse
        ]);
    }

    /**
     * Compte le nombre de likes pour une réponse
     */
    public function countLikes(Reponse $reponse): int
    {
        return $this->count([
            'reponse' => $reponse,
            'type' => 'like'
        ]);
    }

    /**
     * Compte le nombre de dislikes pour une réponse
     */
    public function countDislikes(Reponse $reponse): int
    {
        return $this->count([
            'reponse' => $reponse,
            'type' => 'dislike'
        ]);
    }

    /**
     * Récupère toutes les réactions pour une réponse
     */
    public function findByReponse(Reponse $reponse): array
    {
        return $this->findBy(['reponse' => $reponse]);
    }
}
