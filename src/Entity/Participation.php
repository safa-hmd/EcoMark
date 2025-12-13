<?php

namespace App\Entity;

use App\Repository\ParticipationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParticipationRepository::class)]
class Participation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private ?string $statut = null;

    #[ORM\Column]
    private ?\DateTime $dateParticipation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getDateParticipation(): ?\DateTime
    {
        return $this->dateParticipation;
    }

    public function setDateParticipation(\DateTime $dateParticipation): static
    {
        $this->dateParticipation = $dateParticipation;

        return $this;
    }

    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_CONFIRMEE = 'confirmee';
    public const STATUT_ANNULEE = 'annulee';

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'participations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'participations')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?Evenement $evenement = null;

    public function getEvenement(): ?Evenement
    {
        return $this->evenement;
    }

    public function setEvenement(?Evenement $evenement): static
    {
        $this->evenement = $evenement;

        return $this;
    }


    public function getUser(): ?User
{
    return $this->user;
}

public function setUser(?User $user): static
{
    $this->user = $user;

    return $this;
}



}
