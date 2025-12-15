<?php

namespace App\Entity;

use App\Repository\PointRecyclageRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PointRecyclageRepository::class)]
class PointRecyclage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom du point de recyclage est obligatoire.")]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: "Le nom doit contenir au moins {{ limit }} caractères.",
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $nomPoint = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'adresse est obligatoire.")]
    #[Assert\Length(
        min: 5,
        max: 255,
        minMessage: "L'adresse doit contenir au moins {{ limit }} caractères.",
        maxMessage: "L'adresse ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $adresse = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le type de matière acceptée est obligatoire.")]
    #[Assert\Length(
        max: 100,
        maxMessage: "Le type de matière ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $typeMatiereAcceptee = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "La capacité maximale est obligatoire.")]
    #[Assert\Positive(message: "La capacité maximale doit être un nombre positif.")]
    #[Assert\Range(
        min: 1,
        max: 100000,
        notInRangeMessage: "La capacité maximale doit être entre {{ min }} et {{ max }}."
    )]
    private ?int $capaciteMax = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le responsable est obligatoire.")]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: "Le nom du responsable doit contenir au moins {{ limit }} caractères.",
        maxMessage: "Le nom du responsable ne peut pas dépasser {{ limit }} caractères."
    )]
    #[Assert\Regex(
        pattern: "/^[a-zA-ZÀ-ÿ\s\-']+$/",
        message: "Le nom du responsable ne peut contenir que des lettres, espaces, tirets et apostrophes."
    )]
    private ?string $responsable = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomPoint(): ?string
    {
        return $this->nomPoint;
    }

    public function setNomPoint(string $nomPoint): static
    {
        $this->nomPoint = $nomPoint;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getTypeMatiereAcceptee(): ?string
    {
        return $this->typeMatiereAcceptee;
    }

    public function setTypeMatiereAcceptee(string $typeMatiereAcceptee): static
    {
        $this->typeMatiereAcceptee = $typeMatiereAcceptee;

        return $this;
    }

    public function getCapaciteMax(): ?int
    {
        return $this->capaciteMax;
    }

    public function setCapaciteMax(int $capaciteMax): static
    {
        $this->capaciteMax = $capaciteMax;

        return $this;
    }

    public function getResponsable(): ?string
    {
        return $this->responsable;
    }

    public function setResponsable(string $responsable): static
    {
        $this->responsable = $responsable;

        return $this;
    }
}