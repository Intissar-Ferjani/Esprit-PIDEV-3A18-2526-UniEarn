<?php

namespace App\Entity\contract;

use App\Entity\users\client\Client;
use App\Entity\users\freelancer\Freelancer;
use App\Repository\contract\ContractRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ContractRepository::class)]
#[ORM\Table(name: 'contract')]
#[Assert\Callback('validateDates')]
class Contract
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idContract', type: 'integer')]
    private ?int $idContract = null;

    #[ORM\ManyToOne(targetEntity: ContractTemplate::class)]
    #[ORM\JoinColumn(name: 'idContractTemplate', referencedColumnName: 'idContractTemplate', nullable: false)]
    private ?ContractTemplate $template = null; // @phpstan-ignore doctrine.associationType

    #[ORM\ManyToOne(targetEntity: Client::class)]
    #[ORM\JoinColumn(name: 'idClient', referencedColumnName: 'idClient', nullable: false)]
    private ?Client $client = null; // @phpstan-ignore doctrine.associationType

    #[ORM\ManyToOne(targetEntity: Freelancer::class)]
    #[ORM\JoinColumn(name: 'idFreelancer', referencedColumnName: 'idFreelancer', nullable: false)]
    private ?Freelancer $freelancer = null; // @phpstan-ignore doctrine.associationType

    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Title is required.')]
    #[Assert\Length(min: 3, max: 255)]
    private ?string $title = null; // @phpstan-ignore doctrine.columnType

    #[ORM\Column(name: 'content', type: 'text')]
    #[Assert\NotBlank(message: 'Content is required.')]
    private ?string $content = null; // @phpstan-ignore doctrine.columnType

    #[ORM\Column(name: 'amount', type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Amount is required.')]
    #[Assert\Positive(message: 'Amount must be positive.')]
    private ?string $amount = null; // @phpstan-ignore doctrine.columnType

    #[ORM\Column(name: 'startDate', type: 'date')]
    #[Assert\NotBlank(message: 'Start date is required.')]
    private ?\DateTimeInterface $startDate = null; // @phpstan-ignore doctrine.columnType

    #[ORM\Column(name: 'endDate', type: 'date')]
    #[Assert\NotBlank(message: 'End date is required.')]
    private ?\DateTimeInterface $endDate = null; // @phpstan-ignore doctrine.columnType

    #[ORM\Column(name: 'status', type: 'string', length: 20)]
    private string $status = 'pending';

    #[ORM\Column(name: 'clientSignature', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $clientSignature = null;

    #[ORM\Column(name: 'clientSignatureImage', type: 'text', nullable: true)]
    private ?string $clientSignatureImage = null;

    #[ORM\Column(name: 'freelancerSignature', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $freelancerSignature = null;

    #[ORM\Column(name: 'freelancerSignatureImage', type: 'text', nullable: true)]
    private ?string $freelancerSignatureImage = null;

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

    public function getIdContract(): ?int { return $this->idContract; }

    public function getTemplate(): ?ContractTemplate { return $this->template; }
    public function setTemplate(?ContractTemplate $template): static { $this->template = $template; return $this; }

    public function getClient(): ?Client { return $this->client; }
    public function setClient(?Client $client): static { $this->client = $client; return $this; }

    public function getFreelancer(): ?Freelancer { return $this->freelancer; }
    public function setFreelancer(?Freelancer $freelancer): static { $this->freelancer = $freelancer; return $this; }

    public function getTitle(): ?string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }

    public function getContent(): ?string { return $this->content; }
    public function setContent(string $content): static { $this->content = $content; return $this; }

    public function getAmount(): ?string { return $this->amount; }
    public function setAmount(string $amount): static { $this->amount = $amount; return $this; }

    public function getStartDate(): ?\DateTimeInterface { return $this->startDate; }
    public function setStartDate(\DateTimeInterface $startDate): static { $this->startDate = $startDate; return $this; }

    public function getEndDate(): ?\DateTimeInterface { return $this->endDate; }
    public function setEndDate(\DateTimeInterface $endDate): static { $this->endDate = $endDate; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getClientSignature(): ?\DateTimeInterface { return $this->clientSignature; }
    public function setClientSignature(?\DateTimeInterface $clientSignature): static { $this->clientSignature = $clientSignature; return $this; }

    public function getClientSignatureImage(): ?string { return $this->clientSignatureImage; }
    public function setClientSignatureImage(?string $clientSignatureImage): static { $this->clientSignatureImage = $clientSignatureImage; return $this; }

    public function getFreelancerSignature(): ?\DateTimeInterface { return $this->freelancerSignature; }
    public function setFreelancerSignature(?\DateTimeInterface $freelancerSignature): static { $this->freelancerSignature = $freelancerSignature; return $this; }

    public function getFreelancerSignatureImage(): ?string { return $this->freelancerSignatureImage; }
    public function setFreelancerSignatureImage(?string $freelancerSignatureImage): static { $this->freelancerSignatureImage = $freelancerSignatureImage; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeInterface $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }

    public function isSigned(): bool
    {
        return $this->clientSignature !== null && $this->freelancerSignature !== null;
    }

    public function validateDates(ExecutionContextInterface $context): void
    {
        if ($this->startDate !== null && $this->endDate !== null && $this->endDate <= $this->startDate) {
            $context->buildViolation('End date must be after the start date.')
                ->atPath('endDate')
                ->addViolation();
        }

        if ($this->startDate !== null && $this->startDate < new \DateTime('today')) {
            $context->buildViolation('Start date cannot be in the past.')
                ->atPath('startDate')
                ->addViolation();
        }
    }
}
