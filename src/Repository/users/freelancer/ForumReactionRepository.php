<?php

namespace App\Repository\users\freelancer;

use App\Entity\users\freelancer\ForumReaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ForumReaction> */
class ForumReactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ForumReaction::class);
    }

    public function findByUserAndPost(int $freelancerId, int $postId): ?ForumReaction
    {
        return $this->findOneBy(['freelancer' => $freelancerId, 'postId' => $postId]);
    }

    public function countByPostAndType(int $postId, string $type): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.reactionId)')
            ->where('r.postId = :postId')
            ->andWhere('r.reactionType = :type')
            ->setParameter('postId', $postId)
            ->setParameter('type', $type)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function hasUserReacted(int $freelancerId, int $postId, string $type): bool
    {
        $reaction = $this->findOneBy([
            'freelancer'   => $freelancerId,
            'postId'       => $postId,
            'reactionType' => $type,
        ]);
        return $reaction !== null;
    }
}
