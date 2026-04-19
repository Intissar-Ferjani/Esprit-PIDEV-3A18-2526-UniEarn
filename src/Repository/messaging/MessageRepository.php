<?php

namespace App\Repository\messaging;

use App\Entity\messaging\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /** @return Message[] */
    public function findByChatOrdered(int $chatId): array
    {
        return $this->findBy(['chatId' => $chatId], ['sentDate' => 'ASC']);
    }

    /** @return Message[] newest after a given id (for polling) */
    public function findAfter(int $chatId, int $afterId): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.chatId = :cid AND m.idMessage > :aid')
            ->setParameter('cid', $chatId)
            ->setParameter('aid', $afterId)
            ->orderBy('m.sentDate', 'ASC')
            ->getQuery()->getResult();
    }

    public function countUnreadForUser(int $chatId, int $userId): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.idMessage)')
            ->where('m.chatId = :cid AND m.senderId != :uid AND m.seen = false')
            ->setParameter('cid', $chatId)
            ->setParameter('uid', $userId)
            ->getQuery()->getSingleScalarResult();
    }

    public function markSeenInChat(int $chatId, int $myUserId): void
    {
        $this->createQueryBuilder('m')
            ->update()
            ->set('m.seen', true)
            ->where('m.chatId = :cid AND m.senderId != :uid AND m.seen = false')
            ->setParameter('cid', $chatId)
            ->setParameter('uid', $myUserId)
            ->getQuery()->execute();
    }
}
