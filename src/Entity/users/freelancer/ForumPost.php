<?php

namespace App\Entity\users\freelancer;

use App\Repository\users\freelancer\ForumPostRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ForumPostRepository::class)]
#[ORM\Table(name: 'freelancer_forum_post')]
class ForumPost
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'post_id', type: 'integer')]
    private ?int $postId = null;

    #[ORM\Column(name: 'freelancer_id', type: 'integer')]
    private ?int $freelancerId = null;

    #[ORM\Column(name: 'title', type: 'string', length: 200)]
    #[Assert\NotBlank(message: 'A title is required.')]
    #[Assert\Length(min: 5, max: 200, minMessage: 'Title must be at least {{ limit }} characters.')]
    private string $title = '';

    #[ORM\Column(name: 'content', type: 'text')]
    #[Assert\NotBlank(message: 'Content cannot be empty.')]
    #[Assert\Length(min: 10, minMessage: 'Content must be at least {{ limit }} characters.')]
    private string $content = '';

    #[ORM\Column(name: 'gif_url', type: 'string', length: 500, nullable: true)]
    private ?string $gifUrl = null;

    #[ORM\Column(name: 'category', type: 'string', length: 50)]
    private string $category = 'General';

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(name: 'views', type: 'integer')]
    private int $views = 0;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    // ── Getters & Setters ───────────────────────────────────────────────

    public function getPostId(): ?int { return $this->postId; }
    public function setPostId(?int $id): static { $this->postId = $id; return $this; }

    public function getFreelancerId(): ?int { return $this->freelancerId; }
    public function setFreelancerId(?int $id): static { $this->freelancerId = $id; return $this; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }

    public function getContent(): string { return $this->content; }
    public function setContent(string $content): static { $this->content = $content; return $this; }

    public function getGifUrl(): ?string { return $this->gifUrl; }
    public function setGifUrl(?string $url): static { $this->gifUrl = $url; return $this; }

    public function getCategory(): string { return $this->category; }
    public function setCategory(string $cat): static { $this->category = $cat; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $dt): static { $this->createdAt = $dt; return $this; }

    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeInterface $dt): static { $this->updatedAt = $dt; return $this; }

    public function getViews(): int { return $this->views; }
    public function setViews(int $v): static { $this->views = $v; return $this; }
    public function incrementViews(): static { $this->views++; return $this; }
}
