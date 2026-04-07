<?php

namespace App\Repository\users\freelancer;

use App\Entity\users\freelancer\Portfolio;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PortfolioRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Portfolio::class);
    }

    public function findByFreelancerId(int $freelancerId): ?Portfolio
    {
        return $this->createQueryBuilder('p')
            ->join('p.freelancer', 'f')
            ->where('f.idFreelancer = :id')
            ->setParameter('id', $freelancerId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}