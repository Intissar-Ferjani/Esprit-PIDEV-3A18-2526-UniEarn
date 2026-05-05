<?php

namespace App\Repository\project;

use App\Entity\project\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    public function save(Project $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Project $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByStatus(bool $status): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->setParameter('status', $status)
            ->orderBy('p.idProject', 'DESC')
            ->setMaxResults(1000)
            ->getQuery()
            ->getResult();
    }

    public function findByClient(int $clientId, int $limit = 50): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('IDENTITY(p.client) = :clientId')
            ->setParameter('clientId', $clientId)
            ->orderBy('p.idProject', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByFreelancer(int $freelancerId, int $limit = 50): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('IDENTITY(p.freelancer) = :freelancerId')
            ->setParameter('freelancerId', $freelancerId)
            ->orderBy('p.idProject', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findAvailableProjects(int $limit = 50): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status IN (:availableStatuses)')
            ->setParameter('availableStatuses', ['TODO', 'Review'])
            ->orderBy('p.idProject', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param int[] $ids
     * @return Project[]
     */
    public function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return $this->createQueryBuilder('p')
            ->andWhere('p.idProject IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('p.idProject', 'DESC')
            ->setMaxResults(1000)
            ->getQuery()
            ->getResult();
    }
}
