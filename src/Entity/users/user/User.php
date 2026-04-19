<?php

namespace App\Entity\users\user;

use App\Repository\users\user\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idUser', type: 'integer')]
    private ?int $idUser = null;

    #[ORM\Column(name: 'name', type: 'string', length: 50)]
    #[Assert\NotBlank(message: 'Name is required.')]
    #[Assert\Length(
        min: 2, max: 50,
        minMessage: 'Name must be at least 2 characters.',
        maxMessage: 'Name cannot exceed 50 characters.'
    )]
    #[Assert\Regex(
        pattern: '/^[\p{L}\s\-]+$/u',
        message: 'Name can only contain letters, spaces, and hyphens.'
    )]
    private ?string $name = null;

    #[ORM\Column(name: 'email', type: 'string', length: 50, unique: true)]
    #[Assert\NotBlank(message: 'Email is required.')]
    #[Assert\Email(message: 'Please enter a valid email address.')]
    #[Assert\Length(max: 50, maxMessage: 'Email cannot exceed 50 characters.')]
    private ?string $email = null;

    #[ORM\Column(name: 'password', type: 'string', length: 255)]
    private ?string $password = null;
    
    #[ORM\Column(name: 'profilePicturePath', type: 'string', length: 255, nullable: true)]
    private ?string $profilePicturePath = null;

    #[ORM\Column(name: 'role', type: 'string', length: 50)]
    private string $role = 'CLIENT';

    #[ORM\Column(name: 'activated', type: 'boolean')]
    private bool $activated = true;

    #[ORM\Column(name: 'resetToken', type: 'string', length: 6, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(name: 'resetTokenExpiresAt', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $resetTokenExpiresAt = null;

    #[ORM\Column(name: 'last_active_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastActiveAt = null;

    // ── Getters & Setters ───────────────────────────────────────────────

    public function getIdUser(): ?int { return $this->idUser; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }

    public function getProfilePicturePath(): ?string { return $this->profilePicturePath; }
    public function setProfilePicturePath(?string $path): static { $this->profilePicturePath = $path; return $this; }

    public function getRole(): string { return $this->role; }
    public function setRole(string $role): static { $this->role = $role; return $this; }

    public function isActivated(): bool { return $this->activated; }
    public function setActivated(bool $activated): static { $this->activated = $activated; return $this; }

    public function getResetToken(): ?string { return $this->resetToken; }
    public function setResetToken(?string $resetToken): static { $this->resetToken = $resetToken; return $this; }

    public function getResetTokenExpiresAt(): ?\DateTimeImmutable { return $this->resetTokenExpiresAt; }
    public function setResetTokenExpiresAt(?\DateTimeImmutable $expiresAt): static { $this->resetTokenExpiresAt = $expiresAt; return $this; }

    public function getLastActiveAt(): ?\DateTimeInterface { return $this->lastActiveAt; }
    public function setLastActiveAt(?\DateTimeInterface $dt): static { $this->lastActiveAt = $dt; return $this; }

    public function isOnline(): bool
    {
        if (!$this->lastActiveAt) return false;
        return (new \DateTime())->getTimestamp() - $this->lastActiveAt->getTimestamp() < 300;
    }
}