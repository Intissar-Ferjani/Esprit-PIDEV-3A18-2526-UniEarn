<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260419140725 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE application ADD compatibility_score DOUBLE PRECISION DEFAULT NULL, ADD ai_analysis LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE evaluation ADD sentiment VARCHAR(50) DEFAULT NULL, ADD sentiment_score DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE freelancer ADD iban VARCHAR(255) DEFAULT NULL, ADD swiftCode VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE application DROP compatibility_score, DROP ai_analysis');
        $this->addSql('ALTER TABLE evaluation DROP sentiment, DROP sentiment_score');
        $this->addSql('ALTER TABLE freelancer DROP iban, DROP swiftCode');
    }
}
