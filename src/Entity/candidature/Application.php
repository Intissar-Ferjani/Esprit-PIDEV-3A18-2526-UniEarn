<?php

namespace App\Entity\candidature;

use App\Enum\ApplicationStatus;
use App\Entity\project\Project;
use App\Entity\users\freelancer\Freelancer;
use App\Repository\candidature\ApplicationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ApplicationRepository::class)]
#[ORM\Table(name: 'application')]
#[ORM\HasLifecycleCallbacks]
class Application
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Freelancer::class)]
    #[ORM\JoinColumn(name: 'freelancer_id', referencedColumnName: 'idFreelancer', nullable: false)]
    private Freelancer $freelancer;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(name: 'project_id', referencedColumnName: 'idProject', nullable: false)]
    private Project $project;

    #[ORM\Column(length: 255, enumType: ApplicationStatus::class)]
    private ApplicationStatus $status;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Cover letter is required.')]
    #[Assert\Length(min: 20, minMessage: 'Cover letter must be at least 20 characters.')]
    private string $coverLetter;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Proposed budget is required.')]
    #[Assert\Positive(message: 'Budget must be positive.')]
    private float $proposedBudget;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Estimated duration is required.')]
    #[Assert\Positive(message: 'Duration must be positive.')]
    private int $estimatedDuration;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $appliedAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedAt;

    #[ORM\Column(nullable: true)]
    private ?float $compatibilityScore = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $aiAnalysis = null;

    public function __construct()
    {
        $this->appliedAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->status = ApplicationStatus::PENDING;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFreelancer(): ?Freelancer
    {
        return $this->freelancer;
    }

    public function setFreelancer(?Freelancer $freelancer): static
    {
        $this->freelancer = $freelancer;

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(Project $project): static
    {
        $this->project = $project;

        return $this;
    }

    /** @deprecated Use getProject()->getIdProject() instead */
    public function getProjectId(): ?int
    {
        return $this->project?->getIdProject();
    }

    public function getStatus(): ?ApplicationStatus
    {
        return $this->status;
    }

    public function setStatus(ApplicationStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCoverLetter(): ?string
    {
        return $this->coverLetter;
    }

    public function setCoverLetter(string $coverLetter): static
    {
        $this->coverLetter = $coverLetter;

        return $this;
    }

    public function getProposedBudget(): ?float
    {
        return $this->proposedBudget;
    }

    public function setProposedBudget(float $proposedBudget): static
    {
        $this->proposedBudget = $proposedBudget;

        return $this;
    }

    public function getEstimatedDuration(): ?int
    {
        return $this->estimatedDuration;
    }

    public function setEstimatedDuration(int $estimatedDuration): static
    {
        $this->estimatedDuration = $estimatedDuration;

        return $this;
    }

    public function getAppliedAt(): ?\DateTimeInterface
    {
        return $this->appliedAt;
    }

    public function setAppliedAt(\DateTimeInterface $appliedAt): static
    {
        $this->appliedAt = $appliedAt;

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

    public function getCompatibilityScore(): ?float
    {
        return $this->compatibilityScore;
    }

    public function setCompatibilityScore(?float $compatibilityScore): static
    {
        $this->compatibilityScore = $compatibilityScore;

        return $this;
    }

    public function getAiAnalysis(): ?string
    {
        return $this->aiAnalysis;
    }

    public function setAiAnalysis(?string $aiAnalysis): static
    {
        $this->aiAnalysis = $aiAnalysis;

        return $this;
    }
}
