<?php

namespace App\Repository\users\freelancer;

use App\Entity\users\freelancer\PortfolioItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PortfolioItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PortfolioItem::class);
    }

    public function findByPortfolioId(int $portfolioId): array
    {
        return $this->createQueryBuilder('pi')
            ->join('pi.portfolio', 'p')
            ->where('p.idPortfolio = :id')
            ->setParameter('id', $portfolioId)
            ->getQuery()
            ->getResult();
    }
}