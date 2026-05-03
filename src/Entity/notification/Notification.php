<?php

namespace App\Entity\notification;

use App\Repository\notification\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Table(name: 'notification')]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'user_id', type: 'integer')]
    private int $userId;

    #[ORM\Column(name: 'type', type: 'string', length: 30)]
    private string $type; // LIKE | DISLIKE | COMMENT | MESSAGE

    #[ORM\Column(name: 'content', type: 'text')]
    private string $content;

    #[ORM\Column(name: 'link', type: 'string', length: 255, nullable: true)]
    private ?string $link = null;

    #[ORM\Column(name: 'is_read', type: 'boolean')]
    private bool $isRead = false;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getUserId(): int { return $this->userId; }
    public function setUserId(int $id): static { $this->userId = $id; return $this; }

    public function getType(): string { return $this->type; }
    public function setType(string $t): static { $this->type = $t; return $this; }

    public function getContent(): string { return $this->content; }
    public function setContent(string $c): static { $this->content = $c; return $this; }

    public function getLink(): ?string { return $this->link; }
    public function setLink(?string $l): static { $this->link = $l; return $this; }

    public function isRead(): bool { return $this->isRead; }
    public function setIsRead(bool $r): static { $this->isRead = $r; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
}
