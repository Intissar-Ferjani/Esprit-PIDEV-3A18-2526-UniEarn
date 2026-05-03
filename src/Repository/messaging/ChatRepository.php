<?php

namespace App\Repository\messaging;

use App\Entity\messaging\Chat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Chat> */
class ChatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Chat::class);
    }

    public function findBetween(int $a, int $b): ?Chat
    {
        return $this->createQueryBuilder('c')
            ->where('(c.freelancer1Id = :a AND c.freelancer2Id = :b) OR (c.freelancer1Id = :b AND c.freelancer2Id = :a)')
            ->setParameter('a', $a)->setParameter('b', $b)
            ->getQuery()->getOneOrNullResult();
    }

    /** @return Chat[] */
    public function findForFreelancer(int $freelancerId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.freelancer1Id = :id OR c.freelancer2Id = :id')
            ->setParameter('id', $freelancerId)
            ->orderBy('c.lastMessageAt', 'DESC')
            ->getQuery()->getResult();
    }
}
