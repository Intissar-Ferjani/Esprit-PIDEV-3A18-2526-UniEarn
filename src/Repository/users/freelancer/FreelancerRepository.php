<?php

namespace App\Repository\users\freelancer;

use App\Entity\users\freelancer\Freelancer;
use App\Entity\users\user\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Freelancer> */
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

    public function getSearchQueryBuilder(string $search, string $verification, string $minRating, string $sort)
    {
        $qb = $this->createQueryBuilder('f')
            ->join('f.user', 'u')
            ->addSelect('u')
            ->where('u.activated = true');

        if ($search) {
            $qb->andWhere('u.name LIKE :search OR f.bio LIKE :search OR f.skills LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($verification !== 'All') {
            $qb->andWhere('f.verificationStatus = :v')
               ->setParameter('v', strtolower($verification));
        }

        if ($minRating !== 'Any') {
            $min = (float) str_replace('+', '', $minRating);
            $qb->andWhere('f.rating >= :rating')
               ->setParameter('rating', $min);
        }

        switch ($sort) {
            case 'rating_low':  $qb->orderBy('f.rating', 'ASC'); break;
            case 'price_low':   $qb->orderBy('f.pricePerHour', 'ASC'); break;
            case 'price_high':  $qb->orderBy('f.pricePerHour', 'DESC'); break;
            case 'name_asc':    $qb->orderBy('u.name', 'ASC'); break;
            default:            $qb->orderBy('f.rating', 'DESC'); break;
        }

        return $qb;
    }

    /** @return Freelancer[] */
    public function findAllWithUser(): array
    {
        return $this->getSearchQueryBuilder('', 'All', 'Any', '')->getQuery()->getResult();
    }
}