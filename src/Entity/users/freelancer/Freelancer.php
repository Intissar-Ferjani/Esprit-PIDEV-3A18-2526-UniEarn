<?php

namespace App\Entity\users\freelancer;

use App\Entity\users\user\User;
use App\Repository\users\freelancer\FreelancerRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FreelancerRepository::class)]
#[ORM\Table(name: 'freelancer')]
class Freelancer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idFreelancer', type: 'integer')]
    private ?int $idFreelancer = null;

    #[ORM\Column(name: 'pricePerHour', type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Hourly rate is required.')]
    #[Assert\Positive(message: 'Hourly rate must be greater than 0.')]
    #[Assert\LessThanOrEqual(value: 9999, message: 'Hourly rate cannot exceed 9999 TND.')]
    private string $pricePerHour = '0.00';

    #[ORM\Column(name: 'amount', type: 'decimal', precision: 10, scale: 2)]
    private string $amount = '0.00';

    #[ORM\Column(name: 'rating', type: 'float')]
    private float $rating = 0.0;

    // Stored as comma-separated string in DB e.g. "PHP,Java,Python"
    #[ORM\Column(name: 'skills', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Please add at least one skill.')]
    private string $skills = '';

    #[ORM\Column(name: 'verificationStatus', type: 'string', length: 20)]
    private string $verificationStatus = 'unverified';

    #[ORM\Column(name: 'status', type: 'string', length: 20)]
    private string $status = 'available';

    #[ORM\Column(name: 'bio', type: 'string', length: 500)]
    #[Assert\NotBlank(message: 'Please write a brief bio.')]
    #[Assert\Length(
        min: 10, max: 500,
        minMessage: 'Bio must be at least 10 characters.',
        maxMessage: 'Bio cannot exceed 500 characters.'
    )]
    private string $bio = '';

    #[ORM\Column(name: 'studentCardPath', type: 'string', length: 255, nullable: true)]
    private ?string $studentCardPath = null;

    #[ORM\Column(name: 'cvPath', type: 'string', length: 255, nullable: true)]
    private ?string $cvPath = null;

    #[ORM\Column(name: 'idTask', type: 'integer', nullable: true)]
    private ?int $idTask = null;

    #[ORM\Column(name: 'iban', type: 'string', length: 255, nullable: true)]
    private ?string $iban = null;

    #[ORM\Column(name: 'swiftCode', type: 'string', length: 50, nullable: true)]
    private ?string $swiftCode = null;

    // ── Relationship to User ────────────────────────────────────────────

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'idUser', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    // ── Getters & Setters ───────────────────────────────────────────────

    public function getIdFreelancer(): ?int { return $this->idFreelancer; }

    public function getPricePerHour(): string { return $this->pricePerHour; }
    public function setPricePerHour(string $v): static { $this->pricePerHour = $v; return $this; }

    public function getAmount(): string { return $this->amount; }
    public function setAmount(string $v): static { $this->amount = $v; return $this; }

    public function getRating(): float { return $this->rating; }
    public function setRating(float $v): static { $this->rating = $v; return $this; }

    /** @return array<int, string> */
    public function getSkillsArray(): array
    {
        return $this->skills ? array_values(array_filter(array_map('trim', explode(',', $this->skills)))) : [];
    }

    /** @param array<int, string> $skills */
    public function setSkillsArray(array $skills): static
    {
        $this->skills = implode(',', $skills);
        return $this;
    }

    public function getSkills(): string { return $this->skills; }
    public function setSkills(string $skills): static { $this->skills = $skills; return $this; }

    public function getVerificationStatus(): string { return $this->verificationStatus; }
    public function setVerificationStatus(string $v): static { $this->verificationStatus = $v; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $v): static { $this->status = $v; return $this; }

    public function getBio(): string { return $this->bio; }
    public function setBio(string $bio): static { $this->bio = $bio; return $this; }

    public function getStudentCardPath(): ?string { return $this->studentCardPath; }
    public function setStudentCardPath(?string $v): static { $this->studentCardPath = $v; return $this; }

    public function getCvPath(): ?string { return $this->cvPath; }
    public function setCvPath(?string $v): static { $this->cvPath = $v; return $this; }

    public function getIdTask(): ?int { return $this->idTask; }
    public function setIdTask(?int $v): static { $this->idTask = $v; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }

    public function getIban(): ?string { return $this->iban; }
    public function setIban(?string $iban): static { $this->iban = $iban; return $this; }

    public function getSwiftCode(): ?string { return $this->swiftCode; }
    public function setSwiftCode(?string $swiftCode): static { $this->swiftCode = $swiftCode; return $this; }

    // ── Delegate User fields ────────────────────────────────────────────

    public function getName(): string            { return $this->user->getName(); }
    public function getEmail(): string           { return $this->user->getEmail(); }
    public function getRole(): string             { return $this->user->getRole(); }
    public function isActivated(): bool           { return $this->user->isActivated(); }
    public function getProfilePicturePath(): ?string { return $this->user->getProfilePicturePath(); }
    public function getIdUser(): ?int             { return $this->user->getIdUser(); }
}