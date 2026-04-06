<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260406194536 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('DROP TABLE application');
        $this->addSql('DROP TABLE bank_account');
        $this->addSql('DROP TABLE chat');
        $this->addSql('DROP TABLE evaluation');
        $this->addSql('DROP TABLE freelancer_forum_comment');
        $this->addSql('DROP TABLE freelancer_forum_post');
        $this->addSql('DROP TABLE freelancer_forum_reaction');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE payment');
        $this->addSql('DROP TABLE payment_escrow');
        $this->addSql('DROP TABLE project');
        $this->addSql('DROP TABLE task');
        $this->addSql('ALTER TABLE admin DROP FOREIGN KEY admin_ibfk_1');
        $this->addSql('DROP INDEX iduser ON admin');
        $this->addSql('CREATE INDEX IDX_880E0D76FE6E88D7 ON admin (idUser)');
        $this->addSql('ALTER TABLE admin ADD CONSTRAINT admin_ibfk_1 FOREIGN KEY (idUser) REFERENCES user (idUser) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY client_ibfk_1');
        $this->addSql('ALTER TABLE client CHANGE amount amount DOUBLE PRECISION NOT NULL, CHANGE rating rating DOUBLE PRECISION NOT NULL, CHANGE company company VARCHAR(255) NOT NULL, CHANGE industry industry VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX userid ON client');
        $this->addSql('CREATE INDEX IDX_C74404555FD86D04 ON client (userID)');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT client_ibfk_1 FOREIGN KEY (userID) REFERENCES user (idUser) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contract DROP FOREIGN KEY FK_contract_freelancer');
        $this->addSql('ALTER TABLE contract DROP FOREIGN KEY FK_contract_template');
        $this->addSql('ALTER TABLE contract DROP FOREIGN KEY FK_contract_client');
        $this->addSql('ALTER TABLE contract ADD clientSignatureImage LONGTEXT DEFAULT NULL, ADD freelancerSignatureImage LONGTEXT DEFAULT NULL');
        $this->addSql('DROP INDEX idx_contract_template ON contract');
        $this->addSql('CREATE INDEX IDX_E98F285991B5BE79 ON contract (idContractTemplate)');
        $this->addSql('DROP INDEX idx_contract_client ON contract');
        $this->addSql('CREATE INDEX IDX_E98F2859A455ACCF ON contract (idClient)');
        $this->addSql('DROP INDEX idx_contract_freelancer ON contract');
        $this->addSql('CREATE INDEX IDX_E98F2859CEDACF02 ON contract (idFreelancer)');
        $this->addSql('ALTER TABLE contract ADD CONSTRAINT FK_contract_freelancer FOREIGN KEY (idFreelancer) REFERENCES freelancer (idFreelancer)');
        $this->addSql('ALTER TABLE contract ADD CONSTRAINT FK_contract_template FOREIGN KEY (idContractTemplate) REFERENCES contract_template (idContractTemplate)');
        $this->addSql('ALTER TABLE contract ADD CONSTRAINT FK_contract_client FOREIGN KEY (idClient) REFERENCES client (idClient)');
        $this->addSql('ALTER TABLE freelancer DROP FOREIGN KEY freelancer_ibfk_1');
        $this->addSql('ALTER TABLE freelancer CHANGE pricePerHour pricePerHour DOUBLE PRECISION NOT NULL, CHANGE amount amount DOUBLE PRECISION NOT NULL, CHANGE rating rating DOUBLE PRECISION NOT NULL, CHANGE skills skills VARCHAR(255) NOT NULL, CHANGE verificationStatus verificationStatus VARCHAR(20) NOT NULL, CHANGE status status VARCHAR(20) NOT NULL, CHANGE bio bio VARCHAR(500) NOT NULL');
        $this->addSql('DROP INDEX iduser ON freelancer');
        $this->addSql('CREATE INDEX IDX_4C2ED1E8FE6E88D7 ON freelancer (idUser)');
        $this->addSql('ALTER TABLE freelancer ADD CONSTRAINT freelancer_ibfk_1 FOREIGN KEY (idUser) REFERENCES user (idUser) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE portfolio DROP FOREIGN KEY portfolio_ibfk_1');
        $this->addSql('DROP INDEX freelancerid ON portfolio');
        $this->addSql('CREATE INDEX IDX_A9ED10624DCE947A ON portfolio (freelancerId)');
        $this->addSql('ALTER TABLE portfolio ADD CONSTRAINT portfolio_ibfk_1 FOREIGN KEY (freelancerId) REFERENCES freelancer (idFreelancer) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE portfolioitem DROP FOREIGN KEY portfolioitem_ibfk_1');
        $this->addSql('DROP INDEX idportfolio ON portfolioitem');
        $this->addSql('CREATE INDEX IDX_D6C82F0B55073B34 ON portfolioitem (idPortfolio)');
        $this->addSql('ALTER TABLE portfolioitem ADD CONSTRAINT portfolioitem_ibfk_1 FOREIGN KEY (idPortfolio) REFERENCES portfolio (idPortfolio) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user CHANGE role role VARCHAR(50) NOT NULL, CHANGE activated activated TINYINT(1) NOT NULL');
        $this->addSql('DROP INDEX email ON user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON user (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE application (idApplication INT AUTO_INCREMENT NOT NULL, freelancer_id INT NOT NULL, project_id INT NOT NULL, status VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, cover_letter VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, proposed_budget DOUBLE PRECISION NOT NULL, estimated_duration INT NOT NULL, applied_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE bank_account (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, account_holder_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, iban VARCHAR(34) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, bic VARCHAR(11) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, bank_name VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, is_default TINYINT(1) DEFAULT 0, date_added DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, date_modified DATETIME DEFAULT NULL) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE chat (idChat INT AUTO_INCREMENT NOT NULL, created_At DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, status TINYINT(1) NOT NULL, projectID INT NOT NULL, userID INT NOT NULL) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE evaluation (idEvaluation INT AUTO_INCREMENT NOT NULL, evaluator_id INT NOT NULL, evaluated_id INT NOT NULL, project_id INT NOT NULL, rating INT NOT NULL, comment VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, type VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE freelancer_forum_comment (comment_id INT AUTO_INCREMENT NOT NULL, post_id INT DEFAULT NULL, freelancer_id INT NOT NULL, comment_text TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE freelancer_forum_post (post_id INT AUTO_INCREMENT NOT NULL, freelancer_id INT NOT NULL, title VARCHAR(200) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, content TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME NOT NULL) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE freelancer_forum_reaction (reaction_id INT AUTO_INCREMENT NOT NULL, post_id INT NOT NULL, freelancer_id INT NOT NULL, reaction_type VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'LIKE\' NOT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE message (idMessage INT AUTO_INCREMENT NOT NULL, content VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, sentDate DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, seen TINYINT(1) NOT NULL, ChatID INT NOT NULL, SenderID INT NOT NULL) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE payment (idPayment INT AUTO_INCREMENT NOT NULL, amount DOUBLE PRECISION NOT NULL, datePayment DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, paymentStatus VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, userID INT NOT NULL, taskID INT DEFAULT NULL) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE payment_escrow (id INT AUTO_INCREMENT NOT NULL, contract_id INT NOT NULL, client_id INT NOT NULL, freelancer_id INT NOT NULL, amount NUMERIC(10, 2) NOT NULL, status VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT \'PENDING\' NOT NULL COLLATE `utf8mb4_general_ci` COMMENT \'PENDING, COMPLETED, RELEASED, REFUNDED\', date_creation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, date_completion DATETIME DEFAULT NULL COMMENT \'Quand le projet est livré\', date_liberation DATETIME DEFAULT NULL COMMENT \'Quand l\'\'admin libère le montant\', notes TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci` COMMENT \'Notes admin sur la validation\') DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE project (idProject INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, budget DOUBLE PRECISION NOT NULL, status TINYINT(1) NOT NULL, ClientID INT NOT NULL, freelancerIDD INT DEFAULT NULL, freelancerID INT DEFAULT NULL) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE task (idTask INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, deadline DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, TaskStatus VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, dateAssign DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, role VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, priority VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, idProject INT NOT NULL) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE admin DROP FOREIGN KEY FK_880E0D76FE6E88D7');
        $this->addSql('DROP INDEX idx_880e0d76fe6e88d7 ON admin');
        $this->addSql('CREATE INDEX idUser ON admin (idUser)');
        $this->addSql('ALTER TABLE admin ADD CONSTRAINT FK_880E0D76FE6E88D7 FOREIGN KEY (idUser) REFERENCES `user` (idUser) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C74404555FD86D04');
        $this->addSql('ALTER TABLE client CHANGE amount amount DOUBLE PRECISION DEFAULT \'0\' NOT NULL, CHANGE rating rating DOUBLE PRECISION DEFAULT \'0\' NOT NULL, CHANGE company company VARCHAR(255) DEFAULT \'\' NOT NULL, CHANGE industry industry VARCHAR(255) DEFAULT \'\' NOT NULL');
        $this->addSql('DROP INDEX idx_c74404555fd86d04 ON client');
        $this->addSql('CREATE INDEX userID ON client (userID)');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C74404555FD86D04 FOREIGN KEY (userID) REFERENCES `user` (idUser) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contract DROP FOREIGN KEY FK_E98F285991B5BE79');
        $this->addSql('ALTER TABLE contract DROP FOREIGN KEY FK_E98F2859A455ACCF');
        $this->addSql('ALTER TABLE contract DROP FOREIGN KEY FK_E98F2859CEDACF02');
        $this->addSql('ALTER TABLE contract DROP clientSignatureImage, DROP freelancerSignatureImage');
        $this->addSql('DROP INDEX idx_e98f285991b5be79 ON contract');
        $this->addSql('CREATE INDEX IDX_contract_template ON contract (idContractTemplate)');
        $this->addSql('DROP INDEX idx_e98f2859a455accf ON contract');
        $this->addSql('CREATE INDEX IDX_contract_client ON contract (idClient)');
        $this->addSql('DROP INDEX idx_e98f2859cedacf02 ON contract');
        $this->addSql('CREATE INDEX IDX_contract_freelancer ON contract (idFreelancer)');
        $this->addSql('ALTER TABLE contract ADD CONSTRAINT FK_E98F285991B5BE79 FOREIGN KEY (idContractTemplate) REFERENCES contract_template (idContractTemplate)');
        $this->addSql('ALTER TABLE contract ADD CONSTRAINT FK_E98F2859A455ACCF FOREIGN KEY (idClient) REFERENCES client (idClient)');
        $this->addSql('ALTER TABLE contract ADD CONSTRAINT FK_E98F2859CEDACF02 FOREIGN KEY (idFreelancer) REFERENCES freelancer (idFreelancer)');
        $this->addSql('ALTER TABLE freelancer DROP FOREIGN KEY FK_4C2ED1E8FE6E88D7');
        $this->addSql('ALTER TABLE freelancer CHANGE pricePerHour pricePerHour DOUBLE PRECISION DEFAULT \'0\' NOT NULL, CHANGE amount amount DOUBLE PRECISION DEFAULT \'0\' NOT NULL, CHANGE rating rating DOUBLE PRECISION DEFAULT \'0\' NOT NULL, CHANGE skills skills VARCHAR(255) DEFAULT \'\' NOT NULL, CHANGE verificationStatus verificationStatus VARCHAR(20) DEFAULT \'unverified\' NOT NULL, CHANGE status status VARCHAR(20) DEFAULT \'available\' NOT NULL, CHANGE bio bio VARCHAR(500) DEFAULT \'\' NOT NULL');
        $this->addSql('DROP INDEX idx_4c2ed1e8fe6e88d7 ON freelancer');
        $this->addSql('CREATE INDEX idUser ON freelancer (idUser)');
        $this->addSql('ALTER TABLE freelancer ADD CONSTRAINT FK_4C2ED1E8FE6E88D7 FOREIGN KEY (idUser) REFERENCES `user` (idUser) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE portfolio DROP FOREIGN KEY FK_A9ED10624DCE947A');
        $this->addSql('DROP INDEX idx_a9ed10624dce947a ON portfolio');
        $this->addSql('CREATE INDEX freelancerId ON portfolio (freelancerId)');
        $this->addSql('ALTER TABLE portfolio ADD CONSTRAINT FK_A9ED10624DCE947A FOREIGN KEY (freelancerId) REFERENCES freelancer (idFreelancer) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE portfolioitem DROP FOREIGN KEY FK_D6C82F0B55073B34');
        $this->addSql('DROP INDEX idx_d6c82f0b55073b34 ON portfolioitem');
        $this->addSql('CREATE INDEX idPortfolio ON portfolioitem (idPortfolio)');
        $this->addSql('ALTER TABLE portfolioitem ADD CONSTRAINT FK_D6C82F0B55073B34 FOREIGN KEY (idPortfolio) REFERENCES portfolio (idPortfolio) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `user` CHANGE role role VARCHAR(50) DEFAULT \'CLIENT\' NOT NULL, CHANGE activated activated TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('DROP INDEX uniq_8d93d649e7927c74 ON `user`');
        $this->addSql('CREATE UNIQUE INDEX email ON `user` (email)');
    }
}
