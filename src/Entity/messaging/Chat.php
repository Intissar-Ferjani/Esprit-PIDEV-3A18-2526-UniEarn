<?php

namespace App\Entity\messaging;

use App\Repository\messaging\ChatRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChatRepository::class)]
#[ORM\Table(name: 'chat')]
#[ORM\UniqueConstraint(name: 'uk_conversation', columns: ['freelancer1_id', 'freelancer2_id'])]
class Chat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idChat', type: 'integer')]
    private ?int $idChat = null;

    #[ORM\Column(name: 'freelancer1_id', type: 'integer')]
    private int $freelancer1Id;

    #[ORM\Column(name: 'freelancer2_id', type: 'integer')]
    private int $freelancer2Id;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(name: 'last_message_at', type: 'datetime')]
    private \DateTimeInterface $lastMessageAt;

    public function __construct()
    {
        $this->createdAt     = new \DateTime();
        $this->lastMessageAt = new \DateTime();
    }

    public function getIdChat(): ?int { return $this->idChat; }

    public function getFreelancer1Id(): int { return $this->freelancer1Id; }
    public function setFreelancer1Id(int $id): static { $this->freelancer1Id = $id; return $this; }

    public function getFreelancer2Id(): int { return $this->freelancer2Id; }
    public function setFreelancer2Id(int $id): static { $this->freelancer2Id = $id; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }

    public function getLastMessageAt(): \DateTimeInterface { return $this->lastMessageAt; }
    public function setLastMessageAt(\DateTimeInterface $dt): static { $this->lastMessageAt = $dt; return $this; }

    public function getOtherFreelancerId(int $myId): int
    {
        return $this->freelancer1Id === $myId ? $this->freelancer2Id : $this->freelancer1Id;
    }
}
