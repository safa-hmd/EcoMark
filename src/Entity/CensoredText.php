<?php
namespace App\Entity;

use App\Repository\CensoredTextRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CensoredTextRepository::class)]
#[ORM\Index(columns: ['text_hash'], name: 'idx_text_hash')]
class CensoredText
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 32, unique: true)]
    private ?string $textHash = null;

    #[ORM\Column(type: 'text')]
    private ?string $originalText = null;

    #[ORM\Column(type: 'text')]
    private ?string $censoredText = null;

    #[ORM\Column(type: 'boolean')]
    private ?bool $hasBadWords = false;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastUsedAt = null;

    #[ORM\Column(type: 'integer')]
    private ?int $usageCount = 0;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->lastUsedAt = new \DateTime();
        $this->usageCount = 1;
    }

    // Getters & Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTextHash(): ?string
    {
        return $this->textHash;
    }

    public function setTextHash(string $textHash): self
    {
        $this->textHash = $textHash;
        return $this;
    }

    public function getOriginalText(): ?string
    {
        return $this->originalText;
    }

    public function setOriginalText(string $originalText): self
    {
        $this->originalText = $originalText;
        return $this;
    }

    public function getCensoredText(): ?string
    {
        return $this->censoredText;
    }

    public function setCensoredText(string $censoredText): self
    {
        $this->censoredText = $censoredText;
        return $this;
    }

    public function isHasBadWords(): ?bool
    {
        return $this->hasBadWords;
    }

    public function setHasBadWords(bool $hasBadWords): self
    {
        $this->hasBadWords = $hasBadWords;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getLastUsedAt(): ?\DateTimeInterface
    {
        return $this->lastUsedAt;
    }

    public function setLastUsedAt(?\DateTimeInterface $lastUsedAt): self
    {
        $this->lastUsedAt = $lastUsedAt;
        return $this;
    }

    public function getUsageCount(): ?int
    {
        return $this->usageCount;
    }

    public function setUsageCount(int $usageCount): self
    {
        $this->usageCount = $usageCount;
        return $this;
    }

    public function incrementUsageCount(): self
    {
        $this->usageCount++;
        $this->lastUsedAt = new \DateTime();
        return $this;
    }
}