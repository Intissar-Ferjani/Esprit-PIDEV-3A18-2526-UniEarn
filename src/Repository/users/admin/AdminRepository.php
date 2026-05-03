<?php

namespace App\Repository\users\admin;

use App\Entity\users\admin\Admin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Admin>
 */
class AdminRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Admin::class);
    }

    public function findByUserId(int $userId): ?Admin
    {
        return $this->createQueryBuilder('a')
            ->join('a.user', 'u')
            ->where('u.idUser = :id')
            ->setParameter('id', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}