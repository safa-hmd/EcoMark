<?php

namespace App\Entity;

use App\Repository\PointRecyclageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PointRecyclageRepository::class)]
class PointRecyclage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $lieu = null;

    #[ORM\ManyToOne]
    private ?User $responsable = null;

    #[ORM\Column(length: 255)]
    private ?string $typeDechet = null;

    #[ORM\OneToMany(
        mappedBy: 'pointRecyclage',
        targetEntity: Produit::class,
        cascade: ['persist', 'remove']
    )]
    private Collection $produits;

    public function __construct()
    {
        $this->produits = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(string $lieu): static
    {
        $this->lieu = $lieu;
        return $this;
    }

    public function getResponsable(): ?User
    {
        return $this->responsable;
    }

    public function setResponsable(?User $responsable): static
    {
        $this->responsable = $responsable;
        return $this;
    }

    public function getTypeDechet(): ?string
    {
        return $this->typeDechet;
    }

    public function setTypeDechet(string $typeDechet): static
    {
        $this->typeDechet = $typeDechet;
        return $this;
    }

    /** @return Collection<int, Produit> */
    public function getProduits(): Collection
    {
        return $this->produits;
    }

    public function addProduit(Produit $produit): static
    {
        if (!$this->produits->contains($produit)) {
            $this->produits->add($produit);
            $produit->setPointRecyclage($this);
        }
        return $this;
    }

    public function removeProduit(Produit $produit): static
    {
        if ($this->produits->removeElement($produit)) {
            if ($produit->getPointRecyclage() === $this) {
                $produit->setPointRecyclage(null);
            }
        }
        return $this;
    }
}
