<?php

namespace App\Tests\Unit\contract;

use App\Entity\contract\Contract;
use App\Entity\contract\ContractTemplate;
use App\Entity\users\client\Client;
use App\Entity\users\freelancer\Freelancer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class ContractTest extends TestCase
{
    // ── Test 1 : statut par défaut ───────────────────────────────────────

    public function testDefaultStatusIsPending(): void
    {
        $contract = new Contract();

        $this->assertSame('pending', $contract->getStatus());
    }

    // ── Test 2 : isSigned() — aucune signature ───────────────────────────

    public function testIsSignedReturnsFalseWhenNoSignatures(): void
    {
        $contract = new Contract();

        $this->assertFalse($contract->isSigned());
    }

    // ── Test 3 : isSigned() — seulement le client a signé ───────────────

    public function testIsSignedReturnsFalseWhenOnlyClientSigned(): void
    {
        $contract = new Contract();
        $contract->setClientSignature(new \DateTime());

        $this->assertFalse($contract->isSigned());
    }

    // ── Test 4 : isSigned() — seulement le freelancer a signé ───────────

    public function testIsSignedReturnsFalseWhenOnlyFreelancerSigned(): void
    {
        $contract = new Contract();
        $contract->setFreelancerSignature(new \DateTime());

        $this->assertFalse($contract->isSigned());
    }

    // ── Test 5 : isSigned() — les deux ont signé ────────────────────────

    public function testIsSignedReturnsTrueWhenBothSigned(): void
    {
        $contract = new Contract();
        $contract->setClientSignature(new \DateTime());
        $contract->setFreelancerSignature(new \DateTime());

        $this->assertTrue($contract->isSigned());
    }

    // ── Test 6 : validateDates() — endDate avant startDate ──────────────

    public function testValidateDatesAddsViolationWhenEndDateBeforeStartDate(): void
    {
        $contract = new Contract();
        $contract->setStartDate(new \DateTime('+2 days'));
        $contract->setEndDate(new \DateTime('+1 day')); // endDate < startDate

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->method('atPath')->willReturnSelf();
        $violationBuilder->expects($this->once())->method('addViolation');

        /** @var ExecutionContextInterface&MockObject $context */
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())
            ->method('buildViolation')
            ->with('End date must be after the start date.')
            ->willReturn($violationBuilder);

        $contract->validateDates($context);
    }

    // ── Test 7 : validateDates() — startDate dans le passé ──────────────

    public function testValidateDatesAddsViolationWhenStartDateInPast(): void
    {
        $contract = new Contract();
        $contract->setStartDate(new \DateTime('-1 day'));  // passé
        $contract->setEndDate(new \DateTime('+10 days'));

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->method('atPath')->willReturnSelf();
        $violationBuilder->expects($this->once())->method('addViolation');

        /** @var ExecutionContextInterface&MockObject $context */
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())
            ->method('buildViolation')
            ->with('Start date cannot be in the past.')
            ->willReturn($violationBuilder);

        $contract->validateDates($context);
    }

    // ── Test 8 : validateDates() — dates valides, aucune violation ───────

    public function testValidateDatesNoViolationForValidDates(): void
    {
        $contract = new Contract();
        $contract->setStartDate(new \DateTime('today'));
        $contract->setEndDate(new \DateTime('+30 days'));

        /** @var ExecutionContextInterface&MockObject $context */
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())->method('buildViolation');

        $contract->validateDates($context);
    }

    // ── Test 9 : getters / setters de base ──────────────────────────────

    public function testGettersAndSetters(): void
    {
        $contract = new Contract();
        $contract->setTitle('Service Web UniEarn');
        $contract->setContent('Développement d\'une application web complète.');
        $contract->setAmount('1500.00');
        $contract->setStatus('signed');

        $this->assertSame('Service Web UniEarn', $contract->getTitle());
        $this->assertSame('Développement d\'une application web complète.', $contract->getContent());
        $this->assertSame('1500.00', $contract->getAmount());
        $this->assertSame('signed', $contract->getStatus());
    }

    // ── Test 10 : createdAt est initialisé à la construction ────────────

    public function testCreatedAtIsSetOnConstruct(): void
    {
        $before = new \DateTime();
        $contract = new Contract();
        $after = new \DateTime();

        $this->assertGreaterThanOrEqual($before, $contract->getCreatedAt());
        $this->assertLessThanOrEqual($after, $contract->getCreatedAt());
    }
}
