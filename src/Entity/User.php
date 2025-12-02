<?php

namespace App\Entity;

use App\Repository\UserRepository;
<<<<<<< HEAD
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
=======
>>>>>>> 92c2bfb14d358331dd14c36b6881841a677de329
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(type: 'json')]
    private ?array $role = null;

    #[ORM\Column(length: 255)]
    private ?string $adresse = null;

    #[ORM\Column(length: 255)]
    private ?string $telephone = null;

    #[ORM\Column(length: 255)]
    private ?string $photo = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $lastActivity = null;

<<<<<<< HEAD
    // ✅ AJOUTER cette relation OneToMany :
    #[ORM\OneToMany(targetEntity: Commande::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $commandes;

    // ✅ AJOUTER dans le constructeur :
    public function __construct()
    {
        $this->commandes = new ArrayCollection();
    }

=======
>>>>>>> 92c2bfb14d358331dd14c36b6881841a677de329
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
<<<<<<< HEAD
=======

>>>>>>> 92c2bfb14d358331dd14c36b6881841a677de329
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;
<<<<<<< HEAD
=======

>>>>>>> 92c2bfb14d358331dd14c36b6881841a677de329
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
<<<<<<< HEAD
=======

>>>>>>> 92c2bfb14d358331dd14c36b6881841a677de329
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
<<<<<<< HEAD
=======

>>>>>>> 92c2bfb14d358331dd14c36b6881841a677de329
        return $this;
    }

    public function getRole(): array
    {
        $roles = $this->role;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRole(array $roles): static
    {
        $this->role = $roles;
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): static
    {
        $this->adresse = $adresse;
<<<<<<< HEAD
=======

>>>>>>> 92c2bfb14d358331dd14c36b6881841a677de329
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): static
    {
        $this->telephone = $telephone;
<<<<<<< HEAD
=======

>>>>>>> 92c2bfb14d358331dd14c36b6881841a677de329
        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(string $photo): static
    {
        $this->photo = $photo;
<<<<<<< HEAD
        return $this;
    }

    public function getLastActivity(): ?\DateTimeInterface
=======

        return $this;
    }

        public function getLastActivity(): ?\DateTimeInterface
>>>>>>> 92c2bfb14d358331dd14c36b6881841a677de329
    {
        return $this->lastActivity;
    }

    public function setLastActivity(?\DateTimeInterface $lastActivity): static
    {
        $this->lastActivity = $lastActivity;
        return $this;
    }
<<<<<<< HEAD

    // ✅ AJOUTER ces méthodes pour gérer les commandes :
    /**
     * @return Collection<int, Commande>
     */
    public function getCommandes(): Collection
    {
        return $this->commandes;
    }

    public function addCommande(Commande $commande): static
    {
        if (!$this->commandes->contains($commande)) {
            $this->commandes->add($commande);
            $commande->setUser($this);
        }
        return $this;
    }

    public function removeCommande(Commande $commande): static
    {
        if ($this->commandes->removeElement($commande)) {
            // set the owning side to null (unless already changed)
            if ($commande->getUser() === $this) {
                $commande->setUser(null);
            }
        }
        return $this;
    }
}
=======
}
>>>>>>> 92c2bfb14d358331dd14c36b6881841a677de329
