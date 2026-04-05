<?php

namespace App\Entity\users\admin;

use App\Entity\users\user\User;
use App\Repository\users\admin\AdminRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdminRepository::class)]
#[ORM\Table(name: 'admin')]
class Admin
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idAdmin', type: 'integer')]
    private ?int $idAdmin = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'idUser', referencedColumnName: 'idUser', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    public function getIdAdmin(): ?int { return $this->idAdmin; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }

    // Delegate user fields
    public function getName(): ?string  { return $this->user?->getName(); }
    public function getEmail(): ?string { return $this->user?->getEmail(); }
    public function getIdUser(): ?int   { return $this->user?->getIdUser(); }
}