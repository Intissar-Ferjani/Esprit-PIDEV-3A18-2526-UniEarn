<?php

namespace App\Entity\users\freelancer;

use App\Repository\users\freelancer\PortfolioItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PortfolioItemRepository::class)]
#[ORM\Table(name: 'portfolioitem')]
class PortfolioItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idItem', type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $idItem = null;

    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(name: 'description', type: 'string', length: 500, nullable: true)]
    private ?string $description = null;

    // Stored as JSON string in DB: ["PHP","React"]
    #[ORM\Column(name: 'technologies', type: 'string', length: 500, nullable: true)]
    private ?string $technologies = null;

    #[ORM\Column(name: 'imageUrl', type: 'string', length: 500, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(name: 'projectUrl', type: 'string', length: 255, nullable: true)]
    private ?string $projectUrl = null;

    #[ORM\Column(name: 'githubUrl', type: 'string', length: 255, nullable: true)]
    private ?string $githubUrl = null;

    #[ORM\Column(name: 'created_At', type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\ManyToOne(targetEntity: Portfolio::class)]
    #[ORM\JoinColumn(name: 'idPortfolio', referencedColumnName: 'idPortfolio', nullable: false, onDelete: 'CASCADE')]
    private ?Portfolio $portfolio = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getIdItem(): ?int { return $this->idItem; }

    public function getTitle(): ?string { return $this->title; }
    public function setTitle(?string $v): static { $this->title = $v; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $v): static { $this->description = $v; return $this; }

    /** @return array<int, string> */
    public function getTechnologiesArray(): array
    {
        if (!$this->technologies) return [];
        $decoded = json_decode($this->technologies, true);
        return is_array($decoded) ? $decoded : array_filter(array_map('trim', explode(',', $this->technologies)));
    }

    /** Accepts comma-separated string from form and stores as JSON */
    public function setTechnologiesFromString(?string $v): static
    {
        if (!$v) { $this->technologies = '[]'; return $this; }
        $arr = array_filter(array_map('trim', explode(',', $v)));
        $this->technologies = json_encode(array_values($arr));
        return $this;
    }

    public function getTechnologies(): ?string { return $this->technologies; }
    public function setTechnologies(?string $v): static { $this->technologies = $v; return $this; }

    public function getImageUrl(): ?string { return $this->imageUrl; }
    public function setImageUrl(?string $v): static { $this->imageUrl = $v; return $this; }

    public function getProjectUrl(): ?string { return $this->projectUrl; }
    public function setProjectUrl(?string $v): static { $this->projectUrl = $v; return $this; }

    public function getGithubUrl(): ?string { return $this->githubUrl; }
    public function setGithubUrl(?string $v): static { $this->githubUrl = $v; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }

    public function getPortfolio(): ?Portfolio { return $this->portfolio; }
    public function setPortfolio(Portfolio $portfolio): static { $this->portfolio = $portfolio; return $this; }
}