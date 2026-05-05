<?php

namespace App\Entity\users\freelancer;

use App\Repository\users\freelancer\PortfolioRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PortfolioRepository::class)]
#[ORM\Table(name: 'portfolio')]
class Portfolio
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idPortfolio', type: 'integer')]
    private ?int $idPortfolio = null;

    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Portfolio title is required.')]
    #[Assert\Length(min: 5, minMessage: 'Title must be at least 5 characters.')]
    private string $title;

    #[ORM\Column(name: 'description', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Portfolio description is required.')]
    private string $description;

    #[ORM\Column(name: 'created_At', type: 'datetime')]
    private \DateTimeInterface $createdAt;

    // Links to freelancer table via freelancerId (idFreelancer FK)
    #[ORM\ManyToOne(targetEntity: Freelancer::class)]
    #[ORM\JoinColumn(name: 'freelancer_id', referencedColumnName: 'idFreelancer', nullable: false, onDelete: 'CASCADE')]
    private Freelancer $freelancer;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getIdPortfolio(): ?int { return $this->idPortfolio; }

    public function getTitle(): ?string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(string $description): static { $this->description = $description; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $v): static { $this->createdAt = $v; return $this; }

    public function getFreelancer(): ?Freelancer { return $this->freelancer; }
    public function setFreelancer(Freelancer $freelancer): static { $this->freelancer = $freelancer; return $this; }
}