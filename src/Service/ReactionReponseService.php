<?php
// src/Service/ReactionReponseService.php
namespace App\Service;

use App\Entity\ReactionReponse;
use App\Entity\Reponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class ReactionReponseService
{
    private EntityManagerInterface $em;
    private Security $security;

    public function __construct(EntityManagerInterface $em, Security $security)
    {
        $this->em = $em;
        $this->security = $security;
    }

    public function toggle(Reponse $reponse, string $type): void
    {
        $user = $this->security->getUser();
        if (!$user) {
            throw new \RuntimeException('User must be authenticated');
        }

        // Chercher une réaction existante pour cet utilisateur sur cette réponse
        $existing = $this->em->getRepository(ReactionReponse::class)->findOneBy([
            'user' => $user,
            'reponse' => $reponse
        ]);

        if ($existing) {
            // Si l'utilisateur clique sur la même réaction → on supprime (toggle off)
            if ($existing->getType() === $type) {
                $this->em->remove($existing);
            } else {
                // Si l'utilisateur change de réaction (like → dislike ou inversement)
                $existing->setType($type);
            }
        } else {
            // Créer une nouvelle réaction
            $reaction = new ReactionReponse();
            $reaction->setUser($user);
            $reaction->setReponse($reponse);
            $reaction->setType($type);
            $this->em->persist($reaction);
        }

        $this->em->flush();
    }
}