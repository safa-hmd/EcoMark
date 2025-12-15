<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;


#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30, nullable: false)]
    #[Assert\NotBlank(message: "Le nom est obligatoire.")]
    private ?string $nom = null;

    #[ORM\Column(length: 30, nullable: false)]
    #[Assert\NotBlank(message: "Le prénom est obligatoire.")]
    private ?string $prenom = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: "L'email est obligatoire.")]
    #[Assert\Email(message: "L'email '{{ value }}' n'est pas valide.")]
    private ?string $email = null;

    #[ORM\Column(type: "string")]
    #[Assert\NotBlank(message: "Le mot de passe est obligatoire.")]
    #[Assert\Length(min: 6, minMessage: "Le mot de passe doit contenir au moins {{ limit }} caractères.")]
    #[Assert\Regex(
        pattern: "/^(?=.*[A-Za-z])(?=.*\d).+$/",
        message: "Le mot de passe doit contenir au moins une lettre et un chiffre."
    )]
    private ?string $password = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(length: 255)]
    private ?string $adresse = null;

    #[ORM\Column(length: 8, nullable: true)]
    #[Assert\Regex(
        pattern: "/^[2-9]\d{7}$/",
        message: "Le numéro de téléphone doit contenir exactement 8 chiffres et commencer par 2 à 9."
    )]
    private ?string $telephone = null;

      #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastActivity = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    // -----------------------------
    // REQUIRED BY SYMFONY SECURITY
    // -----------------------------

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /** Symfony 5 compatibility */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER'; // garantit un minimum
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // Ex: $this->plainPassword = null;
    }

    // -----------------------------
    // GETTERS / SETTERS
    // -----------------------------

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
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

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;
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

    public function getLastActivity(): ?\DateTimeInterface
    {
        return $this->lastActivity;
    }

    public function setLastActivity(?\DateTimeInterface $lastActivity): static
    {
        $this->lastActivity = $lastActivity;
        return $this;
    }

    #[ORM\OneToMany(mappedBy: 'client', targetEntity: Reclamation::class)]
    private Collection $reclamations;

    // Réclamations où l'utilisateur est admin
    #[ORM\OneToMany(mappedBy: 'admin', targetEntity: Reclamation::class)]
    private Collection $reclamationsAdmin;

    // Réponses créées par cet admin
    #[ORM\OneToMany(mappedBy: 'admin', targetEntity: Reponse::class)]
    private Collection $reponses;

    public function __construct()
    {
        $this->reclamations = new ArrayCollection();
        $this->reclamationsAdmin = new ArrayCollection();
        $this->reponses = new ArrayCollection();
    }

    // GETTERS
    /** @return Collection|Reclamation[] */
    public function getReclamations(): Collection
    {
        return $this->reclamations;
    }

    /** @return Collection|Reclamation[] */
    public function getReclamationsAdmin(): Collection
    {
        return $this->reclamationsAdmin;
    }

    /** @return Collection|Reponse[] */
    public function getReponses(): Collection
    {
        return $this->reponses;
    }

    public function addReclamation(Reclamation $reclamation): static
    {
        if (!$this->reclamations->contains($reclamation)) {
            $this->reclamations->add($reclamation);
            $reclamation->setClient($this);
        }
        return $this;
    }

    public function addReclamationAdmin(Reclamation $reclamation): static
    {
        if (!$this->reclamationsAdmin->contains($reclamation)) {
            $this->reclamationsAdmin->add($reclamation);
            $reclamation->setAdmin($this);
        }
        return $this;
    }

    public function addReponse(Reponse $reponse): static
    {
        if (!$this->reponses->contains($reponse)) {
            $this->reponses->add($reponse);
            $reponse->setAdmin($this);
        }
        return $this;
    }

}

