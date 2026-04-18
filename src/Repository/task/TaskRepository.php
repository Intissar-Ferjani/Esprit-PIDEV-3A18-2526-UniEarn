<?php

namespace App\Repository\task;

use App\Enum\TaskStatus;
use App\Entity\project\Project;
use App\Entity\task\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    public function save(Task $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Task $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByProject(int $projectId): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.idProject = :projectId')
            ->setParameter('projectId', $projectId)
            ->orderBy('t.idTask', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.taskStatus = :status')
            ->setParameter('status', $status)
            ->orderBy('t.idTask', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByPriority(string $priority): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.priority = :priority')
            ->setParameter('priority', $priority)
            ->orderBy('t.idTask', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOverdueTasks(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.deadline < :now')
            ->andWhere('t.taskStatus != :completed')
            ->setParameter('now', new \DateTime())
            ->setParameter('completed', 'COMPLETED')
            ->orderBy('t.deadline', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.role = :role')
            ->setParameter('role', $role)
            ->orderBy('t.idTask', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Project[] $projects
     * @return array<int, array{percentage:int,total:int,done:int,inProgress:int}>
     */
    public function getProgressByProjects(array $projects): array
    {
        $progressByProject = [];

        foreach ($projects as $project) {
            $projectId = $project->getIdProject();
            if ($projectId === null) {
                continue;
            }

            $progressByProject[$projectId] = [
                'percentage' => 0,
                'total' => 0,
                'done' => 0,
                'inProgress' => 0,
            ];
        }

        if ($progressByProject === []) {
            return [];
        }

        $tasks = $this->createQueryBuilder('t')
            ->select('t', 'p')
            ->join('t.project', 'p')
            ->andWhere('p.idProject IN (:projectIds)')
            ->setParameter('projectIds', array_keys($progressByProject))
            ->getQuery()
            ->getResult();

        foreach ($tasks as $task) {
            $projectId = $task->getProject()?->getIdProject();
            if ($projectId === null || !isset($progressByProject[$projectId])) {
                continue;
            }

            $progressByProject[$projectId]['total']++;

            if ($task->getTaskStatus() === TaskStatus::DONE) {
                $progressByProject[$projectId]['done']++;
                continue;
            }

            if ($task->getTaskStatus() === TaskStatus::IN_PROGRESS) {
                $progressByProject[$projectId]['inProgress']++;
            }
        }

        foreach ($progressByProject as $projectId => $progress) {
            if ($progress['total'] === 0) {
                continue;
            }

            $weightedDone = $progress['done'] + (0.5 * $progress['inProgress']);
            $progressByProject[$projectId]['percentage'] = (int) round(($weightedDone / $progress['total']) * 100);
        }

        return $progressByProject;
    }

    /**
     * @param Project[] $projects
     * @return array<int, array{completed:int,remaining:int,total:int,daysLeft:int|null,deadline:\DateTimeInterface|null,prediction:string}>
     */
    public function getForecastByProjects(array $projects): array
    {
        $forecastByProject = [];

        foreach ($projects as $project) {
            $projectId = $project->getIdProject();
            if ($projectId === null) {
                continue;
            }

            $forecastByProject[$projectId] = [
                'completed' => 0,
                'remaining' => 0,
                'total' => 0,
                'daysLeft' => null,
                'deadline' => null,
                'prediction' => 'on_time',
            ];
        }

        if ($forecastByProject === []) {
            return [];
        }

        $tasks = $this->createQueryBuilder('t')
            ->select('t', 'p')
            ->join('t.project', 'p')
            ->andWhere('p.idProject IN (:projectIds)')
            ->setParameter('projectIds', array_keys($forecastByProject))
            ->orderBy('t.deadline', 'ASC')
            ->getQuery()
            ->getResult();

        $now = new \DateTimeImmutable();

        foreach ($tasks as $task) {
            $projectId = $task->getProject()?->getIdProject();
            if ($projectId === null || !isset($forecastByProject[$projectId])) {
                continue;
            }

            $forecastByProject[$projectId]['total']++;

            if ($task->getTaskStatus() === TaskStatus::DONE) {
                $forecastByProject[$projectId]['completed']++;
            } else {
                $forecastByProject[$projectId]['remaining']++;
            }

            $deadline = $task->getDeadline();
            if (
                $deadline !== null &&
                (
                    $forecastByProject[$projectId]['deadline'] === null ||
                    $deadline > $forecastByProject[$projectId]['deadline']
                )
            ) {
                $forecastByProject[$projectId]['deadline'] = $deadline;
            }
        }

        foreach ($forecastByProject as $projectId => $forecast) {
            $deadline = $forecast['deadline'];

            if ($forecast['remaining'] === 0) {
                $forecastByProject[$projectId]['prediction'] = 'on_time';
                $forecastByProject[$projectId]['daysLeft'] = $deadline instanceof \DateTimeInterface
                    ? (int) $now->diff(\DateTimeImmutable::createFromInterface($deadline))->format('%r%a')
                    : null;
                continue;
            }

            if (!$deadline instanceof \DateTimeInterface) {
                $forecastByProject[$projectId]['prediction'] = 'delayed';
                continue;
            }

            $daysLeft = (int) $now->diff(\DateTimeImmutable::createFromInterface($deadline))->format('%r%a');
            $forecastByProject[$projectId]['daysLeft'] = $daysLeft;

            if ($daysLeft < 0) {
                $forecastByProject[$projectId]['prediction'] = 'delayed';
                continue;
            }

            $safeDaysLeft = max(1, $daysLeft);
            $remaining = $forecast['remaining'];
            $completed = $forecast['completed'];

            if ($completed === 0) {
                $forecastByProject[$projectId]['prediction'] = $remaining > $safeDaysLeft ? 'delayed' : 'on_time';
                continue;
            }

            $completionCapacity = $completed + $safeDaysLeft;
            $forecastByProject[$projectId]['prediction'] = $remaining > $completionCapacity ? 'delayed' : 'on_time';
        }

        return $forecastByProject;
    }
}
