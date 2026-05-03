<?php

namespace App\Repository\users\user;

use App\Entity\users\user\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<User> */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => strtolower(trim($email))]);
    }

    /** @return User[] */
    public function findAllUsers(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.role != :admin')
            ->setParameter('admin', 'ADMIN')
            ->orderBy('u.idUser', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return User[] */
    public function findAllActiveUsers(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.activated = true')
            ->andWhere('u.role != :admin')
            ->setParameter('admin', 'ADMIN')
            ->orderBy('u.idUser', 'DESC')
            ->getQuery()
            ->getResult();
    }
}