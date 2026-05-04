<?php

namespace App\Tests\Entity\candidature;

use App\Entity\candidature\Evaluation;
use App\Entity\users\user\User;
use App\Enum\EvaluationType;
use PHPUnit\Framework\TestCase;

class EvaluationTest extends TestCase
{
    public function testInitialState(): void
    {
        $evaluation = new Evaluation();

        $this->assertNull($evaluation->getId(), 'Static check: ID should be null initially.');
        $this->assertInstanceOf(\DateTime::class, $evaluation->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $evaluation->getUpdatedAt());
        $this->assertFalse($evaluation->isFlagged());
    }

    public function testGettersAndSetters(): void
    {
        $evaluation = new Evaluation();
        $evaluator = $this->createMock(User::class);
        $evaluated = $this->createMock(User::class);

        $evaluation->setEvaluator($evaluator)
            ->setEvaluated($evaluated)
            ->setProjectId(456)
            ->setRating(5)
            ->setComment('Excellent work on this project! Highly recommended.')
            ->setType(EvaluationType::CLIENT_TO_FREELANCER)
            ->setSentiment('pos')
            ->setSentimentScore(0.98)
            ->setIsFlagged(true);

        $this->assertSame($evaluator, $evaluation->getEvaluator());
        $this->assertSame($evaluated, $evaluation->getEvaluated());
        $this->assertEquals(456, $evaluation->getProjectId());
        $this->assertEquals(5, $evaluation->getRating());
        $this->assertEquals('Excellent work on this project! Highly recommended.', $evaluation->getComment());
        $this->assertEquals(EvaluationType::CLIENT_TO_FREELANCER, $evaluation->getType());
        $this->assertEquals('pos', $evaluation->getSentiment());
        $this->assertEquals(0.98, $evaluation->getSentimentScore());
        $this->assertTrue($evaluation->isFlagged());
    }

    public function testUpdatedAtValue(): void
    {
        $evaluation = new Evaluation();
        $oldUpdateAt = $evaluation->getUpdatedAt();
        
        usleep(1000); 
        
        $evaluation->setUpdatedAtValue();
        $this->assertNotSame($oldUpdateAt, $evaluation->getUpdatedAt());
        $this->assertGreaterThan($oldUpdateAt, $evaluation->getUpdatedAt());
    }
}
