<?php

namespace App\Entity\users\freelancer;

use App\Repository\users\freelancer\ForumCommentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ForumCommentRepository::class)]
#[ORM\Table(name: 'freelancer_forum_comment')]
class ForumComment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'comment_id', type: 'integer')]
    private ?int $commentId = null;

    #[ORM\Column(name: 'post_id', type: 'integer')]
    private ?int $postId = null;

    #[ORM\Column(name: 'freelancer_id', type: 'integer')]
    private ?int $freelancerId = null;

    #[ORM\Column(name: 'comment_text', type: 'text')]
    #[Assert\NotBlank(message: 'Your comment cannot be empty.')]
    #[Assert\Length(min: 2, max: 1000, minMessage: 'Your comment is too short.')]
    private string $commentText = '';

    #[ORM\Column(name: 'gif_url', type: 'string', length: 500, nullable: true)]
    private ?string $gifUrl = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    // ── Getters & Setters ───────────────────────────────────────────────

    public function getCommentId(): ?int { return $this->commentId; }
    public function setCommentId(?int $id): static { $this->commentId = $id; return $this; }

    public function getPostId(): ?int { return $this->postId; }
    public function setPostId(?int $id): static { $this->postId = $id; return $this; }

    public function getFreelancerId(): ?int { return $this->freelancerId; }
    public function setFreelancerId(?int $id): static { $this->freelancerId = $id; return $this; }

    public function getCommentText(): string { return $this->commentText; }
    public function setCommentText(string $text): static { $this->commentText = $text; return $this; }

    public function getGifUrl(): ?string { return $this->gifUrl; }
    public function setGifUrl(?string $url): static { $this->gifUrl = $url; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeInterface $dt): static { $this->createdAt = $dt; return $this; }
}
