<?php

namespace App\Entity;


use App\Repository\LivraisonRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LivraisonRepository::class)]
class Livraison
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'adresse est obligatoire")]
    #[Assert\Length(
        min: 10,
        max: 255,
        minMessage: "L'adresse doit contenir au moins {{ limit }} caractères",
        maxMessage: "L'adresse ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $adresse = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "La date de livraison est obligatoire")]
    #[Assert\Type("\DateTimeInterface", message: "La date doit être une date valide")]
    #[Assert\GreaterThanOrEqual(
        "today",
        message: "La date de livraison ne peut pas être dans le passé"
    )]
    private ?\DateTime $dateLivraison = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le statut est obligatoire")]
    #[Assert\Choice(
        choices: ['en_preparation', 'expediee', 'livree',],
        message: "Le statut doit être parmi: en préparation, expédiée,livrée"
    )]
    private ?string $statut = null;

    #[ORM\OneToOne(inversedBy: null, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'commande_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Assert\NotNull(message: "La commande est obligatoire")]
    private ?Commande $commande = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Le numéro de suivi est obligatoire")]
    #[Assert\Length(
        min: 5,
        max: 100,
        minMessage: "Le numéro de suivi doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le numéro de suivi ne peut pas dépasser {{ limit }} caractères"
    )]
    #[Assert\Regex(
        pattern: "/^[A-Z0-9\-_]+$/",
        message: "Le numéro de suivi ne peut contenir que des lettres majuscules, chiffres, tirets et underscores"
    )]
    private ?string $numeroSuivi = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Le transporteur est obligatoire")]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: "Le transporteur doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le transporteur ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $transporteur = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Les frais de livraison sont obligatoires")]
    #[Assert\PositiveOrZero(
        
        message: 'Les frais de livraison doivent être un nombre positif'
    )]
    private ?float $fraisLivraison = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDateLivraison(): ?\DateTime
    {
        return $this->dateLivraison;
    }

    public function setDateLivraison(\DateTime $dateLivraison): static
    {
        $this->dateLivraison = $dateLivraison;
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

    public function getCommande(): ?Commande
    {
        return $this->commande;
    }

    public function setCommande(?Commande $commande): static
    {
        $this->commande = $commande;
        return $this;
    }

    public function getNumeroSuivi(): ?string
    {
        return $this->numeroSuivi;
    }

    public function setNumeroSuivi(string $numeroSuivi): static
    {
        $this->numeroSuivi = $numeroSuivi;
        return $this;
    }

    public function getTransporteur(): ?string
    {
        return $this->transporteur;
    }

    public function setTransporteur(string $transporteur): static
    {
        $this->transporteur = $transporteur;
        return $this;
    }

    public function getFraisLivraison(): ?float
    {
        return $this->fraisLivraison;
    }

    public function setFraisLivraison(float $fraisLivraison): static
    {
        $this->fraisLivraison = $fraisLivraison;
        return $this;
    }
}