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
     * Computes both progress and forecast for all projects.
     * Optimized to use already loaded tasks if provided to avoid redundant DB queries.
     * @param Project[] $projects
     * @param Task[]|null $tasks
     * @return array{progress: array, forecast: array}
     */
    public function getProjectStats(array $projects, ?array $tasks = null): array
    {
        $projectIds = [];
        $progress = [];
        $forecast = [];

        foreach ($projects as $project) {
            $pid = $project->getIdProject();
            if ($pid === null) continue;
            $projectIds[] = $pid;
            $progress[$pid] = ['percentage' => 0, 'total' => 0, 'done' => 0, 'inProgress' => 0];
            $forecast[$pid] = ['completed' => 0, 'remaining' => 0, 'total' => 0, 'daysLeft' => null, 'deadline' => null, 'prediction' => 'on_time'];
        }

        if (empty($projectIds)) {
            return ['progress' => [], 'forecast' => []];
        }

        // Only query if tasks aren't already provided
        if ($tasks === null) {
            $tasks = $this->createQueryBuilder('t')
                ->select('t', 'p')
                ->join('t.project', 'p')
                ->andWhere('p.idProject IN (:projectIds)')
                ->setParameter('projectIds', $projectIds)
                ->orderBy('t.deadline', 'ASC')
                ->getQuery()
                ->getResult();
        }

        $now = new \DateTimeImmutable();

        foreach ($tasks as $task) {
            $pid = $task->getProject()?->getIdProject();
            if ($pid === null || !isset($progress[$pid])) continue;

            // Progress
            $progress[$pid]['total']++;
            if ($task->getTaskStatus() === TaskStatus::DONE) {
                $progress[$pid]['done']++;
            } elseif ($task->getTaskStatus() === TaskStatus::IN_PROGRESS) {
                $progress[$pid]['inProgress']++;
            }

            // Forecast
            $forecast[$pid]['total']++;
            if ($task->getTaskStatus() === TaskStatus::DONE) {
                $forecast[$pid]['completed']++;
            } else {
                $forecast[$pid]['remaining']++;
            }

            $deadline = $task->getDeadline();
            if ($deadline !== null && ($forecast[$pid]['deadline'] === null || $deadline > $forecast[$pid]['deadline'])) {
                $forecast[$pid]['deadline'] = $deadline;
            }
        }

        // Calculate percentages
        foreach ($progress as $pid => $p) {
            if ($p['total'] > 0) {
                $weightedDone = $p['done'] + (0.5 * $p['inProgress']);
                $progress[$pid]['percentage'] = (int) round(($weightedDone / $p['total']) * 100);
            }
        }

        // Calculate predictions
        foreach ($forecast as $pid => $f) {
            $deadline = $f['deadline'];
            if ($f['remaining'] === 0) {
                $forecast[$pid]['daysLeft'] = $deadline instanceof \DateTimeInterface
                    ? (int) $now->diff(\DateTimeImmutable::createFromInterface($deadline))->format('%r%a')
                    : null;
                continue;
            }
            if (!$deadline instanceof \DateTimeInterface) {
                $forecast[$pid]['prediction'] = 'delayed';
                continue;
            }
            $daysLeft = (int) $now->diff(\DateTimeImmutable::createFromInterface($deadline))->format('%r%a');
            $forecast[$pid]['daysLeft'] = $daysLeft;
            if ($daysLeft < 0) {
                $forecast[$pid]['prediction'] = 'delayed';
                continue;
            }
            $safeDaysLeft = max(1, $daysLeft);
            $remaining = $f['remaining'];
            $completed = $f['completed'];
            if ($completed === 0) {
                $forecast[$pid]['prediction'] = $remaining > $safeDaysLeft ? 'delayed' : 'on_time';
            } else {
                $forecast[$pid]['prediction'] = $remaining > ($completed + $safeDaysLeft) ? 'delayed' : 'on_time';
            }
        }

        return ['progress' => $progress, 'forecast' => $forecast];
    }

    /**
     * @param Project[] $projects
     */
    public function getProgressByProjects(array $projects): array
    {
        return $this->getProjectStats($projects)['progress'];
    }

    /**
     * @param Project[] $projects
     */
    public function getForecastByProjects(array $projects): array
    {
        return $this->getProjectStats($projects)['forecast'];
    }
}
