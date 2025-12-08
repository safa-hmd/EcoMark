<?php

namespace App\Entity;
use App\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\ReclamationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReclamationRepository::class)]
class Reclamation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'objet ne peut pas être vide.")]
    #[Assert\Length(
        min: 5,
        max: 10,
        minMessage: "L'objet doit contenir au moins {{ limit }} caractères.",
        maxMessage: "L'objet ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $Objet = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "La description ne peut pas être vide.")]
    #[Assert\Length(
        min: 10,
        max: 200,
        minMessage: "La description doit contenir au moins {{ limit }} caractères.",
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $dateCreation = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = 'en attente';

    #[ORM\ManyToOne(inversedBy: 'reclamations')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $client = null;

    #[ORM\ManyToOne(inversedBy: 'reclamationsAdmin')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $admin = null;

    #[ORM\OneToOne(mappedBy: 'reclamation', cascade: ['persist','remove'],orphanRemoval: true)]
    private ?Reponse $yes = null;

    public function getAdmin(): ?User
{
    return $this->admin;
}

public function setAdmin(?User $admin): static
{
    $this->admin = $admin;
    return $this;
}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getObjet(): ?string
    {
        return $this->Objet;
    }

    public function setObjet(string $Objet): static
    {
        $this->Objet = $Objet;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDateCreation(): ?\DateTime
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTime $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
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

    public function getClient(): User
    {
        return $this->client;
    }

    public function setClient(?User $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function getYes(): ?Reponse
    {
        return $this->yes;
    }

    public function setYes(Reponse $yes): static
    {
        if ($yes->getReclamation() !== $this) {
            $yes->setReclamation($this);
        }

        $this->yes = $yes;

        return $this;
    }
    public function __construct()
{
    $this->dateCreation = new \DateTime(); 
}
}
