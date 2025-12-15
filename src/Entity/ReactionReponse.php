<?php
// src/Entity/ReactionReponse.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ReactionReponse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]

    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Reponse::class, inversedBy: 'reactions')]
    private ?Reponse $reponse = null;

    #[ORM\Column(length: 10)]
    private ?string $type = null; // 'like' ou 'dislike'

    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(User $u): self { $this->user = $u; return $this; }
    public function getReponse(): ?Reponse { return $this->reponse; }
    public function setReponse(?Reponse $r): self { $this->reponse = $r; return $this; }
    public function getType(): ?string { return $this->type; }
    public function setType(string $t): self { $this->type = $t; return $this; }
}
