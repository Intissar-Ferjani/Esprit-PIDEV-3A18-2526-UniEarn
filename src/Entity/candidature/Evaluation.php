<?php

namespace App\Entity\candidature;

use App\Enum\EvaluationType;
use App\Entity\users\user\User;
use App\Repository\candidature\EvaluationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EvaluationRepository::class)]
#[ORM\Table(name: 'evaluation')]
#[ORM\HasLifecycleCallbacks]
class Evaluation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'evaluator_id', referencedColumnName: 'idUser', nullable: false)]
    private ?User $evaluator = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'evaluated_id', referencedColumnName: 'idUser', nullable: false)]
    private ?User $evaluated = null;

    #[ORM\Column(nullable: true)]
    private ?int $projectId = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Rating is required.')]
    #[Assert\Range(min: 1, max: 5, notInRangeMessage: 'Rating must be between 1 and 5.')]
    private ?int $rating = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Comment is required.')]
    #[Assert\Length(min: 15, minMessage: 'Comment must be at least 15 characters.')]
    private ?string $comment = null;

    #[ORM\Column(length: 255, enumType: EvaluationType::class)]
    private ?EvaluationType $type = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    // Getters and setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvaluator(): ?User
    {
        return $this->evaluator;
    }

    public function setEvaluator(?User $evaluator): static
    {
        $this->evaluator = $evaluator;

        return $this;
    }

    public function getEvaluated(): ?User
    {
        return $this->evaluated;
    }

    public function setEvaluated(?User $evaluated): static
    {
        $this->evaluated = $evaluated;

        return $this;
    }

    public function getProjectId(): ?int
    {
        return $this->projectId;
    }

    public function setProjectId(?int $projectId): static
    {
        $this->projectId = $projectId;

        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(int $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getType(): ?EvaluationType
    {
        return $this->type;
    }

    public function setType(EvaluationType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
