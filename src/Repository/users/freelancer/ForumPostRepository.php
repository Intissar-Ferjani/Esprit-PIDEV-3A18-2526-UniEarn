<?php

namespace App\Repository\users\freelancer;

use App\Entity\users\freelancer\ForumPost;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ForumPost> */
class ForumPostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ForumPost::class);
    }

    /** @return ForumPost[] */
    public function findAllOrderedByDate(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** Returns a QueryBuilder for KnpPaginator (all categories) */
    public function queryAllOrdered(): \Doctrine\ORM\QueryBuilder
    {
        return $this->createQueryBuilder('p')->orderBy('p.createdAt', 'DESC');
    }

    /** Returns a QueryBuilder for KnpPaginator (filtered by category) */
    public function queryByCategory(string $category): \Doctrine\ORM\QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->where('p.category = :cat')
            ->setParameter('cat', $category)
            ->orderBy('p.createdAt', 'DESC');
    }

    /** @return ForumPost[] */
    public function findByCategory(string $category): array
    {
        return $this->queryByCategory($category)->getQuery()->getResult();
    }

    /** @return ForumPost[] */
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
