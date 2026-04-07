<?php

namespace App\Repository\users\freelancer;

use App\Entity\users\freelancer\Freelancer;
use App\Entity\users\user\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FreelancerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Freelancer::class);
    }

    public function findByUser(User $user): ?Freelancer
    {
        return $this->findOneBy(['user' => $user]);
    }

    public function findByUserId(int $userId): ?Freelancer
    {
        return $this->createQueryBuilder('f')
            ->join('f.user', 'u')
            ->where('u.idUser = :id')
            ->setParameter('id', $userId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllWithUser(): array
    {
        return $this->createQueryBuilder('f')
            ->join('f.user', 'u')
            ->addSelect('u')
            ->where('u.activated = true')
            ->orderBy('f.rating', 'DESC')
            ->getQuery()
            ->getResult();
    }
}