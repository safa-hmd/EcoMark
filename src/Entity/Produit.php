<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;



#[ORM\Entity(repositoryClass: ProduitRepository::class)]
#[UniqueEntity( 
    fields: ['nomProduit'],
    message: 'Un produit avec ce nom existe déjà.',
    errorPath: 'nomProduit'
)]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom du produit est obligatoire.")]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: "Le nom du produit doit contenir au moins {{ limit }} caractères.",
        maxMessage: "Le nom du produit ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $nomProduit = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    #[Assert\Length(
        min: 10,
        max: 500,
        minMessage: "La description doit contenir au moins {{ limit }} caractères.",
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Le prix est obligatoire.")]
    #[Assert\Positive(message: "Le prix doit être un nombre positif.")]
    #[Assert\Range(
        min: 0.01,
        max: 10000,
        notInRangeMessage: "Le prix doit être entre {{ min }}€ et {{ max }}€."
    )]
    private ?float $prix = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "La quantité en stock est obligatoire.")]
    #[Assert\PositiveOrZero(message: "La quantité en stock ne peut pas être négative.")]
    #[Assert\Range(
        min: 0,
        max: 10000,
        notInRangeMessage: "La quantité doit être entre {{ min }} et {{ max }}."
    )]
    private ?int $quantiteStock = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'état du produit est obligatoire.")]
    #[Assert\Choice(
        choices: ['Disponible', 'En rupture', 'Bientôt disponible', 'Vendu'],
        message: "L'état du produit doit être l'une des valeurs suivantes: Disponible, En rupture, Bientôt disponible, Vendu."
    )]
    private ?string $etatProduit = null;



    //Dans Produit.php
    #[ORM\ManyToMany(targetEntity: Commande::class, mappedBy: 'produits')]
    private $commandes;

   // getter pour la relation ManyToMany avec Commande
public function getCommandes(): Collection
{
    return $this->commandes;
}



    #[ORM\Column]
    //#[Assert\NotNull(message: "La date d'ajout est obligatoire.")]
    //#[Assert\LessThanOrEqual(
      //  value: "now",
        //message: "La date d'ajout ne peut pas être dans le futur."
    //)]
    private ?\DateTime $dateAjout = null;

    #[ORM\ManyToOne]
    private ?PointRecyclage $pointRecyclage = null;
    
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;
   
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomProduit(): ?string
    {
        return $this->nomProduit;
    }

    public function setNomProduit(string $nomProduit): static
    {
        $this->nomProduit = $nomProduit;

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

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getQuantiteStock(): ?int
    {
        return $this->quantiteStock;
    }

    public function setQuantiteStock(int $quantiteStock): static
    {
        $this->quantiteStock = $quantiteStock;

        return $this;
    }

    public function getEtatProduit(): ?string
    {
        return $this->etatProduit;
    }

  public function setEtatProduit(string $etatProduit): static
{
    // Si la quantité est 0, marquer comme 'Vendu'
    if ($this->quantiteStock <= 0) {
        $this->etatProduit = 'Vendu';
    } else {
        $this->etatProduit = $etatProduit;
    }
    
    return $this;
}


    public function getDateAjout(): ?\DateTime
    {
        return $this->dateAjout;
    }

    public function setDateAjout(\DateTime $dateAjout): static
    {
        $this->dateAjout = $dateAjout;

        return $this;
    }

    public function getPointRecyclage(): ?PointRecyclage
    {
        return $this->pointRecyclage;
    }

    public function setPointRecyclage(?PointRecyclage $pointRecyclage): static
    {
        $this->pointRecyclage = $pointRecyclage;

        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;

        return $this;
    }
    


 public function __construct()
{
    $this->commandes = new ArrayCollection();
}




}