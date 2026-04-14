<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260406152126 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE IF NOT EXISTS messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Remove potentially existing tables from previous Java project or failed migrations
        $this->addSql('DROP TABLE IF EXISTS application');
        $this->addSql('DROP TABLE IF EXISTS evaluation');

        // Create application table
        $this->addSql('CREATE TABLE application (id INT AUTO_INCREMENT NOT NULL, freelancer_id INT NOT NULL, project_id INT NOT NULL, status VARCHAR(255) NOT NULL, cover_letter LONGTEXT NOT NULL, proposed_budget DOUBLE PRECISION NOT NULL, estimated_duration INT NOT NULL, applied_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_A45BDDC18545BDF5 (freelancer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Create evaluation table
        $this->addSql('CREATE TABLE evaluation (id INT AUTO_INCREMENT NOT NULL, evaluator_id INT NOT NULL, evaluated_id INT NOT NULL, project_id INT DEFAULT NULL, rating INT NOT NULL, comment LONGTEXT NOT NULL, type VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_1323A57543575BE2 (evaluator_id), INDEX IDX_1323A5757C954B00 (evaluated_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Add foreign keys
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC18545BDF5 FOREIGN KEY (freelancer_id) REFERENCES freelancer (idFreelancer)');
        $this->addSql('ALTER TABLE evaluation ADD CONSTRAINT FK_1323A57543575BE2 FOREIGN KEY (evaluator_id) REFERENCES `user` (idUser)');
        $this->addSql('ALTER TABLE evaluation ADD CONSTRAINT FK_1323A5757C954B00 FOREIGN KEY (evaluated_id) REFERENCES `user` (idUser)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE application DROP FOREIGN KEY FK_A45BDDC18545BDF5');
        $this->addSql('ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A57543575BE2');
        $this->addSql('ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A5757C954B00');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('DROP TABLE application');
        $this->addSql('DROP TABLE evaluation');
    }
}
