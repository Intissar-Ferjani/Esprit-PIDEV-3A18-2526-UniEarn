<?php

namespace App\Tests\Repository\candidature;

use App\Entity\candidature\Evaluation;
use App\Entity\users\user\User;
use App\Enum\EvaluationType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;

class EvaluationRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager = null;
    private $repository = null;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        $this->repository = $this->entityManager->getRepository(Evaluation::class);

        // Purge existing evaluations to ensure isolation
        foreach ($this->repository->findAll() as $eval) {
            $this->entityManager->remove($eval);
        }
        $this->entityManager->flush();
    }

    public function testCalculateReputation(): void
    {
        // 1. Setup users
        $evaluator = new User();
        $evaluator->setName('Evaluator')->setEmail('evaluator_'.uniqid().'@test.com')->setPassword('pass');
        $this->entityManager->persist($evaluator);

        $evaluated = new User();
        $evaluated->setName('Evaluated')->setEmail('evaluated_'.uniqid().'@test.com')->setPassword('pass');
        $this->entityManager->persist($evaluated);

        // 2. Add evaluations
        // Case 1: Positive rating (5) + Positive sentiment (pos)
        // Rating score: (5/5)*80 = 80. Sentiment bonus: 20. Total: 100.
        $eval1 = new Evaluation();
        $eval1->setEvaluator($evaluator)->setEvaluated($evaluated)
            ->setRating(5)->setComment('Perfect work, very professional.')
            ->setType(EvaluationType::CLIENT_TO_FREELANCER)
            ->setSentiment('pos');
        $this->repository->save($eval1);

        // Case 2: Neutral rating (3) + Neutral sentiment (neutral)
        // Rating score: (3/5)*80 = 48. Sentiment bonus: 10. Total: 58.
        $eval2 = new Evaluation();
        $eval2->setEvaluator($evaluator)->setEvaluated($evaluated)
            ->setRating(3)->setComment('Good work, but some delays.')
            ->setType(EvaluationType::CLIENT_TO_FREELANCER)
            ->setSentiment('neutral');
        $this->repository->save($eval2, true);

        // Average: (100 + 58) / 2 = 79
        $reputation = $this->repository->calculateReputation($evaluated);
        $this->assertEquals(79, $reputation);
    }

    public function testCalculateReputationNoEvaluations(): void
    {
        $user = new User();
        $user->setName('New User')->setEmail('new_'.uniqid().'@test.com')->setPassword('pass');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $reputation = $this->repository->calculateReputation($user);
        $this->assertEquals(50.0, $reputation, 'Default reputation should be 50.0');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
