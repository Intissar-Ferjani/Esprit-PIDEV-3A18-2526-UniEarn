<?php

namespace App\Tests\Unit\contract;

use App\Entity\contract\ContractTemplate;
use PHPUnit\Framework\TestCase;

class ContractTemplateTest extends TestCase
{
    // ── Test 1 : getters / setters ───────────────────────────────────────

    public function testGettersAndSetters(): void
    {
        $template = new ContractTemplate();
        $template->setTitle('Modèle Forfait');
        $template->setDescription('Un modèle pour les projets à prix fixe.');
        $template->setContent('Ce contrat lie les deux parties pour un livrable défini.');
        $template->setContractType('fixed_price');

        $this->assertSame('Modèle Forfait', $template->getTitle());
        $this->assertSame('Un modèle pour les projets à prix fixe.', $template->getDescription());
        $this->assertSame('Ce contrat lie les deux parties pour un livrable défini.', $template->getContent());
        $this->assertSame('fixed_price', $template->getContractType());
    }

    // ── Test 2 : description nullable ────────────────────────────────────

    public function testDescriptionIsNullableByDefault(): void
    {
        $template = new ContractTemplate();

        $this->assertNull($template->getDescription());
    }

    // ── Test 3 : idContractTemplate est null avant persistance ───────────

    public function testIdIsNullBeforePersistence(): void
    {
        $template = new ContractTemplate();

        $this->assertNull($template->getIdContractTemplate());
    }

    // ── Test 4 : createdAt initialisé à la construction ──────────────────

    public function testCreatedAtIsSetOnConstruct(): void
    {
        $before = new \DateTime();
        $template = new ContractTemplate();
        $after = new \DateTime();

        $this->assertGreaterThanOrEqual($before, $template->getCreatedAt());
        $this->assertLessThanOrEqual($after, $template->getCreatedAt());
    }

    // ── Test 5 : updatedAt initialisé à la construction ──────────────────

    public function testUpdatedAtIsSetOnConstruct(): void
    {
        $before = new \DateTime();
        $template = new ContractTemplate();
        $after = new \DateTime();

        $this->assertGreaterThanOrEqual($before, $template->getUpdatedAt());
        $this->assertLessThanOrEqual($after, $template->getUpdatedAt());
    }

    // ── Test 6 : types de contrat valides ────────────────────────────────

    /**
     * @dataProvider validContractTypeProvider
     */
    public function testValidContractTypes(string $type): void
    {
        $template = new ContractTemplate();
        $template->setContractType($type);

        $this->assertSame($type, $template->getContractType());
    }

    /** @return array<string, array{string}> */
    public static function validContractTypeProvider(): array
    {
        return [
            'fixed_price' => ['fixed_price'],
            'hourly'      => ['hourly'],
            'milestone'   => ['milestone'],
            'retainer'    => ['retainer'],
        ];
    }
}
