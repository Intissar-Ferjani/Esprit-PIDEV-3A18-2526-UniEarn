<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260504233735 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client CHANGE amount amount NUMERIC(10, 2) NOT NULL');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              freelancer
            CHANGE
              pricePerHour pricePerHour NUMERIC(10, 2) NOT NULL,
            CHANGE
              amount amount NUMERIC(10, 2) NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client CHANGE amount amount DOUBLE PRECISION NOT NULL');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              freelancer
            CHANGE
              pricePerHour pricePerHour DOUBLE PRECISION NOT NULL,
            CHANGE
              amount amount DOUBLE PRECISION NOT NULL
        SQL);
    }
}
