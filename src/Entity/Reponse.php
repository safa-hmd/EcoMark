<?php

namespace App\Entity;

use App\Repository\ReponseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReponseRepository::class)]
class Reponse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $Contenu = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $dateCreation = null;

    #[ORM\OneToOne(inversedBy: 'yes', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?Reclamation $reclamation = null;

    #[ORM\ManyToOne(inversedBy: 'yes')]
    #[ORM\JoinColumn(nullable: true,onDelete: "CASCADE")]
    private ?User $admin = null;

    // ✅ Relation vers les réactions
    #[ORM\OneToMany(mappedBy: 'reponse', targetEntity: ReactionReponse::class, cascade: ['remove'])]
    private Collection $reactions;

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
        $this->reactions = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getContenu(): ?string { return $this->Contenu; }
    public function setContenu(?string $Contenu): static { $this->Contenu = $Contenu; return $this; }

    public function getDateCreation(): ?\DateTime { return $this->dateCreation; }
    public function setDateCreation(?\DateTime $dateCreation): static { $this->dateCreation = $dateCreation; return $this; }

    public function getReclamation(): ?Reclamation { return $this->reclamation; }
    public function setReclamation(Reclamation $reclamation): static { $this->reclamation = $reclamation; return $this; }

    public function getAdmin(): ?User { return $this->admin; }
    public function setAdmin(?User $admin): static { $this->admin = $admin; return $this; }

    // ✅ Méthodes pour gérer les réactions
    public function getReactions(): Collection { return $this->reactions; }

    public function addReaction(ReactionReponse $reaction): static
    {
        if (!$this->reactions->contains($reaction)) {
            $this->reactions->add($reaction);
            $reaction->setReponse($this);
        }
        return $this;
    }

    public function removeReaction(ReactionReponse $reaction): static
    {
        if ($this->reactions->removeElement($reaction)) {
            if ($reaction->getReponse() === $this) {
                $reaction->setReponse(null);
            }
        }
        return $this;
    }
}
