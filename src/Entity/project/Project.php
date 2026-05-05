<?php

namespace App\Entity\project;

use App\Enum\Projectstatus;
use App\Entity\users\client\Client;
use App\Entity\users\freelancer\Freelancer;
use App\Repository\project\ProjectRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\Table(name: 'project')]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idProject', type: 'integer')]
    private ?int $idProject = null;

    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Title is required.')]
    #[Assert\Length(min: 3, max: 255, minMessage: 'Title must be at least 3 characters.')]
    private string $title;

    #[ORM\Column(name: 'description', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Description is required.')]
    #[Assert\Length(min: 10, max: 255, minMessage: 'Description must be at least 10 characters.')]
    private string $description;

    #[ORM\Column(name: 'budget', type: 'float')]
    #[Assert\NotBlank(message: 'Budget is required.')]
    #[Assert\Positive(message: 'Budget must be positive.')]
    private float $budget;

    #[ORM\Column(name: 'status', type: 'string', length: 12, enumType: Projectstatus::class)]
    #[Assert\NotBlank(message: 'Status is required.')]
    private Projectstatus $status;

    #[ORM\ManyToOne(targetEntity: Client::class)]
    #[ORM\JoinColumn(name: 'client_id', referencedColumnName: 'idClient', nullable: false)]
    private Client $client;

    #[ORM\ManyToOne(targetEntity: Freelancer::class)]
    #[ORM\JoinColumn(name: 'freelancer_id', referencedColumnName: 'idFreelancer', nullable: true)]
    private ?Freelancer $freelancer = null;

    public function getIdProject(): ?int
    {
        return $this->idProject;
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

    public function getBudget(): ?float
    {
        return $this->budget;
    }

    public function setBudget(float $budget): static
    {
        $this->budget = $budget;
        return $this;
    }

    public function getStatus(): ?Projectstatus
    {
        return $this->status;
    }

    public function setStatus(Projectstatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(Client $client): static
    {
        $this->client = $client;
        return $this;
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

    #[Assert\Callback]
    public function validateInput(ExecutionContextInterface $context): void
    {
        if ($this->title !== '' && !preg_match("/^[\\p{L}\\s'-]+$/u", $this->title)) {
            $context->buildViolation('Title must contain only letters, spaces, apostrophes, or hyphens.')
                ->atPath('title')
                ->addViolation();
        }

        if ($this->description !== '' && !preg_match("/^[\\p{L}\\p{N}\\s.,;:!?()'\"-]+$/u", $this->description)) {
            $context->buildViolation('Description contains invalid characters.')
                ->atPath('description')
                ->addViolation();
        }
    }
}
