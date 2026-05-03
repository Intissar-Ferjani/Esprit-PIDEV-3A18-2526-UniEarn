<?php

namespace App\Entity\messaging;

use App\Repository\messaging\MessageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ORM\Table(name: 'message')]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idMessage', type: 'integer')]
    private ?int $idMessage = null;

    #[ORM\Column(name: 'content', type: 'string', length: 255)]
    private string $content = '';

    #[ORM\Column(name: 'sentDate', type: 'datetime')]
    private \DateTimeInterface $sentDate;

    #[ORM\Column(name: 'seen', type: 'boolean')]
    private bool $seen = false;

    #[ORM\Column(name: 'ChatID', type: 'integer')]
    private int $chatId;

    #[ORM\Column(name: 'SenderID', type: 'integer')]
    private int $senderId;

    public function __construct()
    {
        $this->sentDate = new \DateTime();
    }

    public function getIdMessage(): ?int { return $this->idMessage; }

    public function getContent(): string { return $this->content; }
    public function setContent(string $c): static { $this->content = $c; return $this; }

    public function getSentDate(): \DateTimeInterface { return $this->sentDate; }

    public function isSeen(): bool { return $this->seen; }
    public function setSeen(bool $seen): static { $this->seen = $seen; return $this; }

    public function getChatId(): int { return $this->chatId; }
    public function setChatId(int $id): static { $this->chatId = $id; return $this; }

    public function getSenderId(): int { return $this->senderId; }
    public function setSenderId(int $id): static { $this->senderId = $id; return $this; }
}
