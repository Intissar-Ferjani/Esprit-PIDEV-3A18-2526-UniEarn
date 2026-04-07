<?php

namespace App\Repository\task;

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
}
