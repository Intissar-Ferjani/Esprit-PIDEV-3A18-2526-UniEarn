<?php

namespace App\Repository\users\user;

use App\Entity\users\user\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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

    public function getAdminSearchQueryBuilder(string $search, ?int $userId, string $role, string $status, string $sort)
    {
        $qb = $this->createQueryBuilder('u')
            ->where('u.role != :admin')
            ->setParameter('admin', 'ADMIN');

        if ($userId) {
            $qb->andWhere('u.idUser = :userId')
               ->setParameter('userId', $userId);
        }

        if ($search) {
            $qb->andWhere('u.name LIKE :s OR u.email LIKE :s')
               ->setParameter('s', '%' . $search . '%');
        }

        if ($role !== 'All') {
            $qb->andWhere('u.role = :role')
               ->setParameter('role', strtoupper($role));
        }

        if ($status === 'Active') {
            $qb->andWhere('u.activated = true');
        } elseif ($status === 'Deactivated') {
            $qb->andWhere('u.activated = false');
        }

        switch ($sort) {
            case 'name_desc': $qb->orderBy('u.name', 'DESC'); break;
            case 'email_asc': $qb->orderBy('u.email', 'ASC'); break;
            case 'role':      $qb->orderBy('u.role', 'ASC'); break;
            case 'status':    $qb->orderBy('u.activated', 'DESC'); break;
            default:          $qb->orderBy('u.name', 'ASC'); break;
        }

        return $qb;
    }

    /** Returns all non-admin users */
    public function findAllUsers(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.role != :admin')
            ->setParameter('admin', 'ADMIN')
            ->orderBy('u.idUser', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** Returns only active non-admin users */
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