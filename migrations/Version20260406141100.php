<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260406141100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create contract_template and contract tables';
    }

    public function up(Schema $schema): void
    {
        // Clean up orphaned InnoDB tablespaces from corrupted tables
        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');

        // Try to create then drop to clean tablespace for contract
        $this->addSql('CREATE TABLE IF NOT EXISTS contract (id INT) ENGINE=InnoDB');
        $this->addSql('DROP TABLE IF EXISTS contract');

        // Try to create then drop to clean tablespace for contract_template
        $this->addSql('CREATE TABLE IF NOT EXISTS contract_template (id INT) ENGINE=InnoDB');
        $this->addSql('DROP TABLE IF EXISTS contract_template');

        // Drop contract_type if it exists
        $this->addSql('CREATE TABLE IF NOT EXISTS contract_type (id INT) ENGINE=InnoDB');
        $this->addSql('DROP TABLE IF EXISTS contract_type');

        $this->addSql('CREATE TABLE contract_template (
            idContractTemplate INT AUTO_INCREMENT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            content LONGTEXT NOT NULL,
            contractType VARCHAR(50) NOT NULL,
            createdAt DATETIME NOT NULL,
            updatedAt DATETIME NOT NULL,
            PRIMARY KEY(idContractTemplate)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE contract (
            idContract INT AUTO_INCREMENT NOT NULL,
            idContractTemplate INT NOT NULL,
            idClient INT NOT NULL,
            idFreelancer INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            content LONGTEXT NOT NULL,
            amount NUMERIC(10, 2) NOT NULL,
            startDate DATE NOT NULL,
            endDate DATE NOT NULL,
            status VARCHAR(20) NOT NULL,
            clientSignature DATETIME DEFAULT NULL,
            freelancerSignature DATETIME DEFAULT NULL,
            createdAt DATETIME NOT NULL,
            updatedAt DATETIME NOT NULL,
            INDEX IDX_contract_template (idContractTemplate),
            INDEX IDX_contract_client (idClient),
            INDEX IDX_contract_freelancer (idFreelancer),
            PRIMARY KEY(idContract),
            CONSTRAINT FK_contract_template FOREIGN KEY (idContractTemplate) REFERENCES contract_template (idContractTemplate),
            CONSTRAINT FK_contract_client FOREIGN KEY (idClient) REFERENCES client (idClient),
            CONSTRAINT FK_contract_freelancer FOREIGN KEY (idFreelancer) REFERENCES freelancer (idFreelancer)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS contract');
        $this->addSql('DROP TABLE IF EXISTS contract_template');
    }
}
