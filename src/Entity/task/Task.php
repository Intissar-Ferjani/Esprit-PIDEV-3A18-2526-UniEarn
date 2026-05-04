<?php

namespace App\Entity\task;

use App\Enum\TaskStatus;
use App\Entity\project\Project;
use App\Repository\task\TaskRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\Table(name: 'task')]
class Task
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idTask', type: 'integer')]
    private ?int $idTask = null;

    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Title is required.')]
    #[Assert\Length(min: 3, max: 255, minMessage: 'Title must be at least 3 characters.')]
    private ?string $title = null;

    #[ORM\Column(name: 'description', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Description is required.')]
    #[Assert\Length(min: 10, max: 255, minMessage: 'Description must be at least 10 characters.')]
    private ?string $description = null;

    #[ORM\Column(name: 'deadline', type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: 'Deadline is required.')]
    private ?\DateTimeInterface $deadline = null;

    #[ORM\Column(name: 'TaskStatus', type: 'string', length: 50, enumType: TaskStatus::class)]
    #[Assert\NotBlank(message: 'Status is required.')]
    private TaskStatus $taskStatus = TaskStatus::TODO;

    #[ORM\Column(name: 'dateAssign', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateAssign = null;

    #[ORM\Column(name: 'role', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Role is required.')]
    private ?string $role = null;

    #[ORM\Column(name: 'priority', type: 'string', length: 50)]
    #[Assert\NotBlank(message: 'Priority is required.')]
    #[Assert\Choice(choices: ['High', 'Medium', 'Low'], message: 'Choose a valid priority: High, Medium, or Low.')]
    private string $priority = 'Medium';

    #[ORM\Column(name: 'submission_link', type: 'string', length: 255, nullable: true)]
    private ?string $submissionLink = null;

    #[ORM\Column(name: 'submission_file', type: 'string', length: 255, nullable: true)]
    private ?string $submissionFile = null;

    #[ORM\Column(name: 'client_feedback', type: Types::TEXT, nullable: true)]
    private ?string $clientFeedback = null;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(name: 'idProject', referencedColumnName: 'idProject', nullable: false)]
    private ?Project $project = null;

    public function __construct()
    {
        $this->dateAssign = new \DateTime();
        $this->deadline = new \DateTime();
    }

    public function getIdTask(): ?int
    {
        return $this->idTask;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDeadline(): ?\DateTimeInterface
    {
        return $this->deadline;
    }

    public function setDeadline(\DateTimeInterface $deadline): static
    {
        $this->deadline = $deadline;
        return $this;
    }

    public function getTaskStatus(): TaskStatus
    {
        return $this->taskStatus;
    }

    public function setTaskStatus(TaskStatus $taskStatus): static
    {
        $this->taskStatus = $taskStatus;
        return $this;
    }

    public function getDateAssign(): ?\DateTimeInterface
    {
        return $this->dateAssign;
    }

    public function setDateAssign(\DateTimeInterface $dateAssign): static
    {
        $this->dateAssign = $dateAssign;
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    public function getSubmissionLink(): ?string
    {
        return $this->submissionLink;
    }

    public function setSubmissionLink(?string $submissionLink): static
    {
        $this->submissionLink = $submissionLink;
        return $this;
    }

    public function getSubmissionFile(): ?string
    {
        return $this->submissionFile;
    }

    public function setSubmissionFile(?string $submissionFile): static
    {
        $this->submissionFile = $submissionFile;
        return $this;
    }

    public function getClientFeedback(): ?string
    {
        return $this->clientFeedback;
    }

    public function setClientFeedback(?string $clientFeedback): static
    {
        $this->clientFeedback = $clientFeedback;
        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        $this->project = $project;
        return $this;
    }

    public function isOverdue(): bool
    {
        if ($this->deadline === null) {
            return false;
        }
        return $this->deadline < new \DateTime() && $this->taskStatus !== TaskStatus::DONE;
    }

    #[Assert\Callback]
    public function validateInput(ExecutionContextInterface $context): void
    {
        if ($this->title !== null && $this->title !== '' && !preg_match("/^[\\p{L}\\p{N}\\s.,;:!?()'\\/\\-]+$/u", $this->title)) {
            $context->buildViolation('Title must contain only letters, numbers, spaces, and basic punctuation.')
                ->atPath('title')
                ->addViolation();
        }

        if ($this->description !== null && $this->description !== '' && !preg_match("/^[\\p{L}\\p{N}\\s.,;:!?()'\"-]+$/u", $this->description)) {
            $context->buildViolation('Description contains invalid characters.')
                ->atPath('description')
                ->addViolation();
        }

        if ($this->role !== null && $this->role !== '' && !preg_match("/^[\\p{L}\\p{N}\\s.,;:!?()'\\/\\-]+$/u", $this->role)) {
            $context->buildViolation('Role must contain only letters, numbers, spaces, and basic punctuation.')
                ->atPath('role')
                ->addViolation();
        }
    }
}
