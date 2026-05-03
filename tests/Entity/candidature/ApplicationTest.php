<?php

namespace App\Tests\Entity\candidature;

use App\Entity\candidature\Application;
use App\Entity\users\freelancer\Freelancer;
use App\Enum\ApplicationStatus;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    public function testInitialState(): void
    {
        $application = new Application();

        $this->assertNull($application->getId(), 'Static check: ID should be null initially.');
        $this->assertEquals(ApplicationStatus::PENDING, $application->getStatus());
        $this->assertInstanceOf(\DateTime::class, $application->getAppliedAt());
        $this->assertInstanceOf(\DateTime::class, $application->getUpdatedAt());
    }

    public function testGettersAndSetters(): void
    {
        $application = new Application();
        $freelancer = $this->createMock(Freelancer::class);
        $date = new \DateTime('2024-01-01');

        $application->setFreelancer($freelancer)
            ->setProjectId(123)
            ->setStatus(ApplicationStatus::ACCEPTED)
            ->setCoverLetter('Test cover letter with at least 20 characters.')
            ->setProposedBudget(1500.50)
            ->setEstimatedDuration(30)
            ->setAppliedAt($date)
            ->setCompatibilityScore(85.5)
            ->setAiAnalysis('Positive analysis');

        $this->assertSame($freelancer, $application->getFreelancer());
        $this->assertEquals(123, $application->getProjectId());
        $this->assertEquals(ApplicationStatus::ACCEPTED, $application->getStatus());
        $this->assertEquals('Test cover letter with at least 20 characters.', $application->getCoverLetter());
        $this->assertEquals(1500.50, $application->getProposedBudget());
        $this->assertEquals(30, $application->getEstimatedDuration());
        $this->assertEquals($date, $application->getAppliedAt());
        $this->assertEquals(85.5, $application->getCompatibilityScore());
        $this->assertEquals('Positive analysis', $application->getAiAnalysis());
    }

    public function testUpdatedAtValue(): void
    {
        $application = new Application();
        $oldUpdateAt = $application->getUpdatedAt();
        
        // Wait a small bit to ensure timestamp would change if updated
        usleep(1000); 
        
        $application->setUpdatedAtValue();
        $this->assertNotSame($oldUpdateAt, $application->getUpdatedAt());
        $this->assertGreaterThan($oldUpdateAt, $application->getUpdatedAt());
    }
}
