<?php

namespace App\Repository\notification;

use App\Entity\notification\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /** @return Notification[] */
    public function findUnread(int $userId): array
    {
        return $this->findBy(['userId' => $userId, 'isRead' => false], ['createdAt' => 'DESC'], 20);
    }

    public function countUnread(int $userId): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.userId = :uid AND n.isRead = false')
            ->setParameter('uid', $userId)
            ->getQuery()->getSingleScalarResult();
    }

    public function markAllRead(int $userId): void
    {
        $this->createQueryBuilder('n')
            ->update()->set('n.isRead', true)
            ->where('n.userId = :uid AND n.isRead = false')
            ->setParameter('uid', $userId)
            ->getQuery()->execute();
    }
}
