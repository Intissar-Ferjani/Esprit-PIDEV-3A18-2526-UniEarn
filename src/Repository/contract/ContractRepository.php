<?php

namespace App\Repository\contract;

use App\Entity\contract\Contract;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ContractRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contract::class);
    }

    public function findByClientId(int $clientId, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.client = :clientId')
            ->setParameter('clientId', $clientId);

        if ($status) {
            $qb->andWhere('c.status = :status')->setParameter('status', $status);
        }

        return $qb->orderBy('c.createdAt', 'DESC')->getQuery()->getResult();
    }

    public function findByFreelancerId(int $freelancerId, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.freelancer = :freelancerId')
            ->setParameter('freelancerId', $freelancerId);

        if ($status) {
            $qb->andWhere('c.status = :status')->setParameter('status', $status);
        }

        return $qb->orderBy('c.createdAt', 'DESC')->getQuery()->getResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.status = :status')
            ->setParameter('status', $status)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAllOrderedByDate(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
