<?php

namespace App\Repository\users\client;

use App\Entity\users\client\Client;
use App\Entity\users\user\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    public function findByUser(User $user): ?Client
    {
        return $this->findOneBy(['user' => $user]);
    }

    public function findByUserId(int $userId): ?Client
    {
        return $this->createQueryBuilder('c')
            ->join('c.user', 'u')
            ->where('u.idUser = :id')
            ->setParameter('id', $userId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function companyExists(string $companyName, ?int $excludeUserId = null): bool
    {
        if (empty(trim($companyName))) return false;

        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c.idClient)')
            ->where('LOWER(c.company) = LOWER(:company)')
            ->setParameter('company', trim($companyName));

        if ($excludeUserId) {
            $qb->join('c.user', 'u')
               ->andWhere('u.idUser != :uid')
               ->setParameter('uid', $excludeUserId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /** Returns all clients with their user data eagerly loaded */
    public function findAllWithUser(): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.user', 'u')
            ->addSelect('u')
            ->orderBy('c.idClient', 'DESC')
            ->getQuery()
            ->getResult();
    }
}