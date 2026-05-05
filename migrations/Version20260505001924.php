<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Rename FK columns to snake_case+_id convention.
 * admin.user_id and client.user_id are already renamed — skipped.
 * contract FK columns are already renamed but FKs were missing — add them.
 */
final class Version20260505001924 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename FK columns to snake_case+_id Doctrine convention';
    }

    public function up(Schema $schema): void
    {
        // ── contract: fix orphaned rows (contract_template_id=0) then add FKs ──
        $this->addSql('UPDATE contract SET contract_template_id = (SELECT MIN(idContractTemplate) FROM contract_template) WHERE contract_template_id NOT IN (SELECT idContractTemplate FROM contract_template)');
        $this->addSql('UPDATE contract SET client_id = (SELECT MIN(idClient) FROM client) WHERE client_id NOT IN (SELECT idClient FROM client)');
        $this->addSql('UPDATE contract SET freelancer_id = (SELECT MIN(idFreelancer) FROM freelancer) WHERE freelancer_id NOT IN (SELECT idFreelancer FROM freelancer)');
        $this->addSql('ALTER TABLE contract ADD CONSTRAINT FK_E98F28594771D675 FOREIGN KEY (contract_template_id) REFERENCES contract_template (idContractTemplate)');
        $this->addSql('ALTER TABLE contract ADD CONSTRAINT FK_E98F285919EB6921 FOREIGN KEY (client_id) REFERENCES client (idClient)');
        $this->addSql('ALTER TABLE contract ADD CONSTRAINT FK_E98F28598545BDF5 FOREIGN KEY (freelancer_id) REFERENCES freelancer (idFreelancer)');
        $this->addSql('CREATE INDEX IDX_E98F28594771D675 ON contract (contract_template_id)');
        $this->addSql('CREATE INDEX IDX_E98F285919EB6921 ON contract (client_id)');
        $this->addSql('CREATE INDEX IDX_E98F28598545BDF5 ON contract (freelancer_id)');

        // ── freelancer: idUser → user_id ─────────────────────────────────────
        $this->addSql('ALTER TABLE freelancer DROP FOREIGN KEY FK_4C2ED1E8FE6E88D7');
        $this->addSql('DROP INDEX IDX_4C2ED1E8FE6E88D7 ON freelancer');
        $this->addSql('ALTER TABLE freelancer CHANGE idUser user_id INT NOT NULL');
        $this->addSql('ALTER TABLE freelancer ADD CONSTRAINT FK_4C2ED1E8A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (idUser) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_4C2ED1E8A76ED395 ON freelancer (user_id)');

        // ── portfolio: freelancerId → freelancer_id ──────────────────────────
        $this->addSql('ALTER TABLE portfolio DROP FOREIGN KEY FK_A9ED10624DCE947A');
        $this->addSql('DROP INDEX IDX_A9ED10624DCE947A ON portfolio');
        $this->addSql('ALTER TABLE portfolio CHANGE freelancerId freelancer_id INT NOT NULL');
        $this->addSql('ALTER TABLE portfolio ADD CONSTRAINT FK_A9ED10628545BDF5 FOREIGN KEY (freelancer_id) REFERENCES freelancer (idFreelancer) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_A9ED10628545BDF5 ON portfolio (freelancer_id)');

        // ── portfolioitem: idPortfolio → portfolio_id ────────────────────────
        $this->addSql('ALTER TABLE portfolioitem DROP FOREIGN KEY FK_D6C82F0B55073B34');
        $this->addSql('DROP INDEX IDX_D6C82F0B55073B34 ON portfolioitem');
        $this->addSql('ALTER TABLE portfolioitem CHANGE idPortfolio portfolio_id INT NOT NULL');
        $this->addSql('ALTER TABLE portfolioitem ADD CONSTRAINT FK_D6C82F0BB96B5643 FOREIGN KEY (portfolio_id) REFERENCES portfolio (idPortfolio) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_D6C82F0BB96B5643 ON portfolioitem (portfolio_id)');

        // ── project: ClientID → client_id, freelancerID → freelancer_id ─────
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE2804AB20');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE76A0B4B2');
        $this->addSql('DROP INDEX IDX_2FB3D0EE76A0B4B2 ON project');
        $this->addSql('DROP INDEX IDX_2FB3D0EE2804AB20 ON project');
        $this->addSql('ALTER TABLE project CHANGE ClientID client_id INT NOT NULL, CHANGE freelancerID freelancer_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE19EB6921 FOREIGN KEY (client_id) REFERENCES client (idClient)');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE8545BDF5 FOREIGN KEY (freelancer_id) REFERENCES freelancer (idFreelancer)');
        $this->addSql('CREATE INDEX IDX_2FB3D0EE19EB6921 ON project (client_id)');
        $this->addSql('CREATE INDEX IDX_2FB3D0EE8545BDF5 ON project (freelancer_id)');

        // ── task: idProject → project_id ─────────────────────────────────────
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB253F0ABB1C');
        $this->addSql('DROP INDEX IDX_527EDB253F0ABB1C ON task');
        $this->addSql('ALTER TABLE task CHANGE idProject project_id INT NOT NULL');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25166D1F9C FOREIGN KEY (project_id) REFERENCES project (idProject)');
        $this->addSql('CREATE INDEX IDX_527EDB25166D1F9C ON task (project_id)');
    }

    public function down(Schema $schema): void
    {
        // ── contract: remove FK constraints ──────────────────────────────────
        $this->addSql('ALTER TABLE contract DROP FOREIGN KEY FK_E98F28594771D675');
        $this->addSql('ALTER TABLE contract DROP FOREIGN KEY FK_E98F285919EB6921');
        $this->addSql('ALTER TABLE contract DROP FOREIGN KEY FK_E98F28598545BDF5');
        $this->addSql('DROP INDEX IDX_E98F28594771D675 ON contract');
        $this->addSql('DROP INDEX IDX_E98F285919EB6921 ON contract');
        $this->addSql('DROP INDEX IDX_E98F28598545BDF5 ON contract');

        // ── freelancer: user_id → idUser ──────────────────────────────────────
        $this->addSql('ALTER TABLE freelancer DROP FOREIGN KEY FK_4C2ED1E8A76ED395');
        $this->addSql('DROP INDEX IDX_4C2ED1E8A76ED395 ON freelancer');
        $this->addSql('ALTER TABLE freelancer CHANGE user_id idUser INT NOT NULL');
        $this->addSql('ALTER TABLE freelancer ADD CONSTRAINT FK_4C2ED1E8FE6E88D7 FOREIGN KEY (idUser) REFERENCES `user` (idUser) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_4C2ED1E8FE6E88D7 ON freelancer (idUser)');

        // ── portfolio: freelancer_id → freelancerId ───────────────────────────
        $this->addSql('ALTER TABLE portfolio DROP FOREIGN KEY FK_A9ED10628545BDF5');
        $this->addSql('DROP INDEX IDX_A9ED10628545BDF5 ON portfolio');
        $this->addSql('ALTER TABLE portfolio CHANGE freelancer_id freelancerId INT NOT NULL');
        $this->addSql('ALTER TABLE portfolio ADD CONSTRAINT FK_A9ED10624DCE947A FOREIGN KEY (freelancerId) REFERENCES freelancer (idFreelancer) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_A9ED10624DCE947A ON portfolio (freelancerId)');

        // ── portfolioitem: portfolio_id → idPortfolio ─────────────────────────
        $this->addSql('ALTER TABLE portfolioitem DROP FOREIGN KEY FK_D6C82F0BB96B5643');
        $this->addSql('DROP INDEX IDX_D6C82F0BB96B5643 ON portfolioitem');
        $this->addSql('ALTER TABLE portfolioitem CHANGE portfolio_id idPortfolio INT NOT NULL');
        $this->addSql('ALTER TABLE portfolioitem ADD CONSTRAINT FK_D6C82F0B55073B34 FOREIGN KEY (idPortfolio) REFERENCES portfolio (idPortfolio) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_D6C82F0B55073B34 ON portfolioitem (idPortfolio)');

        // ── project: client_id → ClientID, freelancer_id → freelancerID ──────
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE19EB6921');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE8545BDF5');
        $this->addSql('DROP INDEX IDX_2FB3D0EE19EB6921 ON project');
        $this->addSql('DROP INDEX IDX_2FB3D0EE8545BDF5 ON project');
        $this->addSql('ALTER TABLE project CHANGE client_id ClientID INT NOT NULL, CHANGE freelancer_id freelancerID INT DEFAULT NULL');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE2804AB20 FOREIGN KEY (ClientID) REFERENCES client (idClient)');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE76A0B4B2 FOREIGN KEY (freelancerID) REFERENCES freelancer (idFreelancer)');
        $this->addSql('CREATE INDEX IDX_2FB3D0EE76A0B4B2 ON project (freelancerID)');
        $this->addSql('CREATE INDEX IDX_2FB3D0EE2804AB20 ON project (ClientID)');

        // ── task: project_id → idProject ──────────────────────────────────────
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25166D1F9C');
        $this->addSql('DROP INDEX IDX_527EDB25166D1F9C ON task');
        $this->addSql('ALTER TABLE task CHANGE project_id idProject INT NOT NULL');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB253F0ABB1C FOREIGN KEY (idProject) REFERENCES project (idProject)');
        $this->addSql('CREATE INDEX IDX_527EDB253F0ABB1C ON task (idProject)');
    }
}
