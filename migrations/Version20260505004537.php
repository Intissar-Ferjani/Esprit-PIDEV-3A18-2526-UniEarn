<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260505004537 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC1166D1F9C FOREIGN KEY (project_id) REFERENCES project (idProject)');
        $this->addSql('CREATE INDEX IDX_A45BDDC1166D1F9C ON application (project_id)');
        $this->addSql('ALTER TABLE evaluation ADD CONSTRAINT FK_1323A575166D1F9C FOREIGN KEY (project_id) REFERENCES project (idProject)');
        $this->addSql('CREATE INDEX IDX_1323A575166D1F9C ON evaluation (project_id)');
        $this->addSql('ALTER TABLE freelancer_forum_comment ADD CONSTRAINT FK_AF3B69E38545BDF5 FOREIGN KEY (freelancer_id) REFERENCES freelancer (idFreelancer) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_AF3B69E38545BDF5 ON freelancer_forum_comment (freelancer_id)');
        $this->addSql('ALTER TABLE freelancer_forum_post ADD CONSTRAINT FK_E30414248545BDF5 FOREIGN KEY (freelancer_id) REFERENCES freelancer (idFreelancer) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_E30414248545BDF5 ON freelancer_forum_post (freelancer_id)');
        $this->addSql('ALTER TABLE freelancer_forum_reaction ADD CONSTRAINT FK_D9EBD67D8545BDF5 FOREIGN KEY (freelancer_id) REFERENCES freelancer (idFreelancer) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_D9EBD67D8545BDF5 ON freelancer_forum_reaction (freelancer_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE application DROP FOREIGN KEY FK_A45BDDC1166D1F9C');
        $this->addSql('DROP INDEX IDX_A45BDDC1166D1F9C ON application');
        $this->addSql('ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A575166D1F9C');
        $this->addSql('DROP INDEX IDX_1323A575166D1F9C ON evaluation');
        $this->addSql('ALTER TABLE freelancer_forum_comment DROP FOREIGN KEY FK_AF3B69E38545BDF5');
        $this->addSql('DROP INDEX IDX_AF3B69E38545BDF5 ON freelancer_forum_comment');
        $this->addSql('ALTER TABLE freelancer_forum_post DROP FOREIGN KEY FK_E30414248545BDF5');
        $this->addSql('DROP INDEX IDX_E30414248545BDF5 ON freelancer_forum_post');
        $this->addSql('ALTER TABLE freelancer_forum_reaction DROP FOREIGN KEY FK_D9EBD67D8545BDF5');
        $this->addSql('DROP INDEX IDX_D9EBD67D8545BDF5 ON freelancer_forum_reaction');
    }
}
