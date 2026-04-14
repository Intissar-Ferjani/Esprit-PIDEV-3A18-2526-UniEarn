<?php

namespace App\Repository\candidature;

use App\Entity\candidature\Evaluation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Evaluation>
 *
 * @method Evaluation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Evaluation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Evaluation[]    findAll()
 * @method Evaluation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EvaluationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evaluation::class);
    }

    public function save(Evaluation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Evaluation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Calculates a reputation score (0-100) for a user.
     */
    public function calculateReputation(\App\Entity\users\user\User $user): float
    {
        $evals = $this->findBy(['evaluated' => $user]);
        if (empty($evals)) return 50.0; // Neutral starting point

        $totalScore = 0;
        foreach ($evals as $eval) {
            // Rating (1-5) contributes up to 80% of the score
            $ratingScore = ($eval->getRating() / 5) * 80;
            
            // Sentiment contributes up to 20%
            $sentimentBonus = 0;
            if ($eval->getSentiment() === 'pos') $sentimentBonus = 20;
            elseif ($eval->getSentiment() === 'neutral') $sentimentBonus = 10;
            
            $totalScore += ($ratingScore + $sentimentBonus);
        }

        return round($totalScore / count($evals), 2);
    }
}
