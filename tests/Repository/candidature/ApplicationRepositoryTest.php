<?php

namespace App\Tests\Repository\candidature;

use App\Entity\candidature\Application;
use App\Entity\users\freelancer\Freelancer;
use App\Entity\users\user\User;
use App\Enum\ApplicationStatus;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;

class ApplicationRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager = null;
    private \App\Repository\candidature\ApplicationRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $manager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        
        if (!$manager instanceof EntityManagerInterface) {
            throw new \RuntimeException('Expected EntityManagerInterface');
        }
        
        $this->entityManager = $manager;
        $this->repository = $this->entityManager->getRepository(Application::class);

        // Purge existing applications to ensure isolation
        foreach ($this->repository->findAll() as $app) {
            $this->entityManager->remove($app);
        }
        $this->entityManager->flush();
    }

    public function testSaveAndFind(): void
    {
        // 1. Setup dependencies
        $user = new User();
        $user->setName('Test User')
            ->setEmail('test_' . uniqid() . '@example.com')
            ->setPassword('password')
            ->setRole('FREELANCER');
        $this->entityManager->persist($user);

        $freelancer = new Freelancer();
        $freelancer->setUser($user)
            ->setPricePerHour(25.0)
            ->setBio('A test freelancer bio for integration testing.')
            ->setSkills('PHP, Symfony');
        $this->entityManager->persist($freelancer);

        // 2. Create and Save Application
        $application = new Application();
        $application->setFreelancer($freelancer)
            ->setProjectId(1)
            ->setCoverLetter('This is a test cover letter with more than 20 characters.')
            ->setProposedBudget(100.0)
            ->setEstimatedDuration(5)
            ->setStatus(ApplicationStatus::PENDING);

        $this->repository->save($application, true);

        // 3. Verify
        $found = $this->repository->find($application->getId());
        $this->assertNotNull($found);
        $this->assertEquals(1, $found->getProjectId());
        $this->assertEquals(ApplicationStatus::PENDING, $found->getStatus());
    }

    public function testFindByProjectId(): void
    {
        // Add multiple applications for same project
        // (Assuming user and freelancer already exist or we create new ones)
        $user = new User();
        $user->setName('Project Finder User')
            ->setEmail('finder_' . uniqid() . '@example.com')
            ->setPassword('password');
        $this->entityManager->persist($user);

        $freelancer = new Freelancer();
        $freelancer->setUser($user)
            ->setSkills('PHP')
            ->setBio('Bio bio bio bio bio');
        $this->entityManager->persist($freelancer);

        $app1 = new Application();
        $app1->setFreelancer($freelancer)->setProjectId(99)->setCoverLetter('Letter 1 for project 99')->setProposedBudget(50)->setEstimatedDuration(1);
        
        $app2 = new Application();
        $app2->setFreelancer($freelancer)->setProjectId(99)->setCoverLetter('Letter 2 for project 99')->setProposedBudget(60)->setEstimatedDuration(2);

        $this->repository->save($app1);
        $this->repository->save($app2, true);

        $results = $this->repository->findByProjectId(99);
        $this->assertCount(2, $results);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
