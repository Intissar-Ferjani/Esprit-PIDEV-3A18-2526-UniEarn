<?php

namespace App\Entity\users\client;

use App\Entity\users\user\User;
use App\Repository\users\client\ClientRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
#[ORM\Table(name: 'client')]
class Client
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idClient', type: 'integer')]
    private ?int $idClient = null;

    #[ORM\Column(name: 'amount', type: 'float')]
    #[Assert\PositiveOrZero(message: 'Budget cannot be negative.')]
    #[Assert\LessThanOrEqual(value: 999999, message: 'Budget cannot exceed 999,999 TND.')]
    private float $amount = 0.0;

    #[ORM\Column(name: 'rating', type: 'float')]
    private float $rating = 0.0;

    #[ORM\Column(name: 'company', type: 'string', length: 255, unique: true)]
    #[Assert\NotBlank(message: 'Company name is required.')]
    #[Assert\Length(
        min: 2, max: 100,
        minMessage: 'Company name must be at least 2 characters.',
        maxMessage: 'Company name cannot exceed 100 characters.'
    )]
    private ?string $company = null;

    #[Assert\Choice(
    choices: [
        'Technology & IT',
        'Marketing & Advertising',
        'Design & Creative',
        'Writing & Content',
        'Business & Consulting',
        'Education & Training',
        'Healthcare',
        'Finance & Accounting',
        'Legal Services',
        'Real Estate',
        'E-commerce & Retail',
        'Manufacturing',
        'Hospitality & Tourism',
        'Construction',
        'Other',
    ],
    message: 'Please select a valid industry.'
    )]
    private ?string $industry = null;

    // ── Relationship to User (mirrors Java's Client extends User + userID FK) ──

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'userID', referencedColumnName: 'idUser', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    // ── Getters & Setters ───────────────────────────────────────────────

    public function getIdClient(): ?int { return $this->idClient; }

    public function getAmount(): float { return $this->amount; }
    public function setAmount(float $amount): static { $this->amount = $amount; return $this; }

    public function getRating(): float { return $this->rating; }
    public function setRating(float $rating): static { $this->rating = $rating; return $this; }

    public function getCompany(): ?string { return $this->company; }
    public function setCompany(string $company): static { $this->company = $company; return $this; }

    public function getIndustry(): ?string { return $this->industry; }
    public function setIndustry(string $industry): static { $this->industry = $industry; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }

    // ── Delegate User fields (mirrors Java's inherited getters) ─────────

    public function getName(): ?string       { return $this->user?->getName(); }
    public function getEmail(): ?string      { return $this->user?->getEmail(); }
    public function getRole(): string        { return $this->user?->getRole() ?? 'CLIENT'; }
    public function isActivated(): bool      { return $this->user?->isActivated() ?? true; }
    public function getProfilePicturePath(): ?string { return $this->user?->getProfilePicturePath(); }
    public function getIdUser(): ?int        { return $this->user?->getIdUser(); }
}