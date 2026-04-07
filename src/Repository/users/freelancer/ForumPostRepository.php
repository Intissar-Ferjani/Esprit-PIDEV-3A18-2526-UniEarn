<?php

namespace App\Repository\users\freelancer;

use App\Entity\users\freelancer\ForumPost;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ForumPostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ForumPost::class);
    }

    public function findAllOrderedByDate(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.category = :cat')
            ->setParameter('cat', $category)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search posts by title or content
     */
    public function searchByContent(string $query): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.title LIKE :q')
            ->orWhere('p.content LIKE :q')
            ->setParameter('q', '%' . $query . '%')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
