<?php

namespace App\Entity\contract;

use App\Repository\contract\ContractTemplateRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ContractTemplateRepository::class)]
#[ORM\Table(name: 'contract_template')]
class ContractTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idContractTemplate', type: 'integer')]
    private ?int $idContractTemplate = null;

    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Title is required.')]
    #[Assert\Length(min: 3, max: 255)]
    private ?string $title = null; // @phpstan-ignore doctrine.columnType

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    #[Assert\Length(max: 1000, maxMessage: 'Description cannot exceed 1000 characters.')]
    private ?string $description = null;

    #[ORM\Column(name: 'content', type: 'text')]
    #[Assert\NotBlank(message: 'Content is required.')]
    #[Assert\Length(min: 10, minMessage: 'Content must be at least 10 characters.')]
    private ?string $content = null; // @phpstan-ignore doctrine.columnType

    #[ORM\Column(name: 'contractType', type: 'string', length: 50)]
    #[Assert\NotBlank(message: 'Contract type is required.')]
    #[Assert\Choice(choices: ['fixed_price', 'hourly', 'milestone', 'retainer'], message: 'Invalid contract type.')]
    private ?string $contractType = null; // @phpstan-ignore doctrine.columnType

    #[ORM\Column(name: 'createdAt', type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(name: 'updatedAt', type: 'datetime')]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    // ── Getters & Setters ───────────────────────────────────────────────

    public function getIdContractTemplate(): ?int { return $this->idContractTemplate; }

    public function getTitle(): ?string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getContent(): ?string { return $this->content; }
    public function setContent(string $content): static { $this->content = $content; return $this; }

    public function getContractType(): ?string { return $this->contractType; }
    public function setContractType(string $contractType): static { $this->contractType = $contractType; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeInterface $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }
}
