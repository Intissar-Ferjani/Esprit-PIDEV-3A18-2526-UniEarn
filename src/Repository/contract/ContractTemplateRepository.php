<?php

namespace App\Repository\contract;

use App\Entity\contract\ContractTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ContractTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContractTemplate::class);
    }

    public function findAllOrderedByDate(): array
    {
        return $this->createQueryBuilder('ct')
            ->orderBy('ct.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('ct')
            ->where('ct.contractType = :type')
            ->setParameter('type', $type)
            ->orderBy('ct.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
