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
    #[Assert\Length(min: 2, max: 50)]
    private ?string $name = null;

    #[ORM\Column(name: 'email', type: 'string', length: 50, unique: true)]
    #[Assert\NotBlank(message: 'Email is required.')]
    #[Assert\Email(message: 'Please enter a valid email.')]
    private ?string $email = null;

    #[ORM\Column(name: 'password', type: 'string', length: 255)]
    private ?string $password = null;

    #[ORM\Column(name: 'profilePicturePath', type: 'string', length: 255, nullable: true)]
    private ?string $profilePicturePath = null;

    #[ORM\Column(name: 'role', type: 'string', length: 50)]
    private string $role = 'CLIENT';

    #[ORM\Column(name: 'activated', type: 'boolean')]
    private bool $activated = true;

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
}