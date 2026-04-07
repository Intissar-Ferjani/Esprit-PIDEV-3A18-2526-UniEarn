<?php

namespace App\Repository\users\freelancer;

use App\Entity\users\freelancer\ForumComment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ForumCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ForumComment::class);
    }

    public function findByPostId(int $postId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.postId = :postId')
            ->setParameter('postId', $postId)
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countByPostId(int $postId): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.commentId)')
            ->where('c.postId = :postId')
            ->setParameter('postId', $postId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
