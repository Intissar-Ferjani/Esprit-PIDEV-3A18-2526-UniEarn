<?php

namespace App\Repository\log;

use App\Entity\log\ActivityLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityLog>
 */
class ActivityLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityLog::class);
    }

    public function findRecentLogs(int $limit = 50, ?int $userId = null, ?string $actionType = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit);

        if ($userId !== null) {
            $qb->andWhere('a.userId = :userId')
               ->setParameter('userId', $userId);
        }

        if ($actionType !== null && $actionType !== '') {
            $qb->andWhere('a.actionType = :actionType')
               ->setParameter('actionType', $actionType);
        }

        return $qb->getQuery()->getResult();
    }
}
