<?php

namespace App\Entity\users\freelancer;

use App\Repository\users\freelancer\ForumReactionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ForumReactionRepository::class)]
#[ORM\Table(name: 'freelancer_forum_reaction')]
#[ORM\UniqueConstraint(name: 'user_post_unique', columns: ['freelancer_id', 'post_id'])]
class ForumReaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'reaction_id', type: 'integer')]
    private ?int $reactionId = null;

    #[ORM\Column(name: 'post_id', type: 'integer')]
    private ?int $postId = null;

    #[ORM\Column(name: 'freelancer_id', type: 'integer')]
    private ?int $freelancerId = null;

    #[ORM\Column(name: 'reaction_type', type: 'string', length: 20)]
    private string $reactionType = 'LIKE';

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    // ── Getters & Setters ───────────────────────────────────────────────

    public function getReactionId(): ?int { return $this->reactionId; }
    public function setReactionId(?int $id): static { $this->reactionId = $id; return $this; }

    public function getPostId(): ?int { return $this->postId; }
    public function setPostId(?int $id): static { $this->postId = $id; return $this; }

    public function getFreelancerId(): ?int { return $this->freelancerId; }
    public function setFreelancerId(?int $id): static { $this->freelancerId = $id; return $this; }

    public function getReactionType(): string { return $this->reactionType; }
    public function setReactionType(string $type): static { $this->reactionType = $type; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeInterface $dt): static { $this->createdAt = $dt; return $this; }
}
