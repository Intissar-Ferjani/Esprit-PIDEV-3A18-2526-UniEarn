<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260405173135 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE admin DROP FOREIGN KEY User');
        $this->addSql('ALTER TABLE bank_account DROP FOREIGN KEY fk_bank_account_user');
        $this->addSql('ALTER TABLE chat DROP FOREIGN KEY IdProject');
        $this->addSql('ALTER TABLE chat DROP FOREIGN KEY IdUser');
        $this->addSql('ALTER TABLE contract DROP FOREIGN KEY id_project');
        $this->addSql('ALTER TABLE contract DROP FOREIGN KEY idClient');
        $this->addSql('ALTER TABLE contract DROP FOREIGN KEY idPayment');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY idChat');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY idSender');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY id_user');
        $this->addSql('ALTER TABLE payment_escrow DROP FOREIGN KEY fk_escrow_client');
        $this->addSql('ALTER TABLE payment_escrow DROP FOREIGN KEY fk_escrow_contract');
        $this->addSql('ALTER TABLE payment_escrow DROP FOREIGN KEY fk_escrow_freelancer');
        $this->addSql('DROP TABLE admin');
        $this->addSql('DROP TABLE application');
        $this->addSql('DROP TABLE bank_account');
        $this->addSql('DROP TABLE chat');
        $this->addSql('DROP TABLE contract');
        $this->addSql('DROP TABLE contract_template');
        $this->addSql('DROP TABLE contrat_type');
        $this->addSql('DROP TABLE forum_private_message');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE payment');
        $this->addSql('DROP TABLE payment_escrow');
        $this->addSql('DROP TABLE project');
        $this->addSql('DROP TABLE task');
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY UserID');
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY UserID');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C74404555FD86D04 FOREIGN KEY (userID) REFERENCES `user` (idUser) ON DELETE CASCADE');
        $this->addSql('DROP INDEX userid ON client');
        $this->addSql('CREATE INDEX IDX_C74404555FD86D04 ON client (userID)');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT UserID FOREIGN KEY (userID) REFERENCES user (idUser) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP INDEX ApplicationID ON freelancer');
        $this->addSql('DROP INDEX TaskID ON freelancer');
        $this->addSql('DROP INDEX PortfolioID ON freelancer');
        $this->addSql('ALTER TABLE freelancer DROP idApplication, DROP idPortfolio, CHANGE skills skills VARCHAR(255) NOT NULL, CHANGE verificationStatus verificationStatus VARCHAR(20) NOT NULL, CHANGE status status VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE freelancer ADD CONSTRAINT FK_4C2ED1E8FE6E88D7 FOREIGN KEY (idUser) REFERENCES `user` (idUser) ON DELETE CASCADE');
        $this->addSql('DROP INDEX userid ON freelancer');
        $this->addSql('CREATE INDEX IDX_4C2ED1E8FE6E88D7 ON freelancer (idUser)');
        $this->addSql('ALTER TABLE freelancer_forum_comment MODIFY comment_id INT NOT NULL');
        $this->addSql('ALTER TABLE freelancer_forum_comment DROP FOREIGN KEY freelancerID');
        $this->addSql('ALTER TABLE freelancer_forum_comment DROP FOREIGN KEY postID');
        $this->addSql('DROP INDEX freelancerID ON freelancer_forum_comment');
        $this->addSql('DROP INDEX postID ON freelancer_forum_comment');
        $this->addSql('DROP INDEX `primary` ON freelancer_forum_comment');
        $this->addSql('ALTER TABLE freelancer_forum_comment ADD content LONGTEXT NOT NULL, ADD author_name VARCHAR(100) NOT NULL, DROP comment_text, CHANGE post_id post_id INT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE comment_id id INT AUTO_INCREMENT NOT NULL, CHANGE freelancer_id author_id INT NOT NULL');
        $this->addSql('ALTER TABLE freelancer_forum_comment ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE freelancer_forum_post MODIFY post_id INT NOT NULL');
        $this->addSql('ALTER TABLE freelancer_forum_post DROP FOREIGN KEY freelancerPOST');
        $this->addSql('DROP INDEX freelancerPOST ON freelancer_forum_post');
        $this->addSql('DROP INDEX `primary` ON freelancer_forum_post');
        $this->addSql('ALTER TABLE freelancer_forum_post ADD author_name VARCHAR(100) NOT NULL, ADD author_id INT NOT NULL, DROP freelancer_id, DROP updated_at, CHANGE title title VARCHAR(255) NOT NULL, CHANGE content content LONGTEXT NOT NULL, CHANGE category category VARCHAR(50) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE views views INT NOT NULL, CHANGE post_id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE freelancer_forum_post ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE freelancer_forum_reaction MODIFY reaction_id INT NOT NULL');
        $this->addSql('ALTER TABLE freelancer_forum_reaction DROP FOREIGN KEY freelancerREACT');
        $this->addSql('ALTER TABLE freelancer_forum_reaction DROP FOREIGN KEY postREACT');
        $this->addSql('DROP INDEX postREACT ON freelancer_forum_reaction');
        $this->addSql('DROP INDEX freelancerREACT ON freelancer_forum_reaction');
        $this->addSql('DROP INDEX `primary` ON freelancer_forum_reaction');
        $this->addSql('ALTER TABLE freelancer_forum_reaction ADD type VARCHAR(10) NOT NULL, DROP reaction_type, DROP created_at, CHANGE reaction_id id INT AUTO_INCREMENT NOT NULL, CHANGE freelancer_id user_id INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX user_post_unique ON freelancer_forum_reaction (user_id, post_id)');
        $this->addSql('ALTER TABLE freelancer_forum_reaction ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE portfolio DROP FOREIGN KEY freelancer');
        $this->addSql('ALTER TABLE portfolio DROP FOREIGN KEY freelancer');
        $this->addSql('ALTER TABLE portfolio CHANGE created_At created_At DATETIME NOT NULL');
        $this->addSql('ALTER TABLE portfolio ADD CONSTRAINT FK_A9ED10624DCE947A FOREIGN KEY (freelancerId) REFERENCES freelancer (idFreelancer) ON DELETE CASCADE');
        $this->addSql('DROP INDEX freelancer ON portfolio');
        $this->addSql('CREATE INDEX IDX_A9ED10624DCE947A ON portfolio (freelancerId)');
        $this->addSql('ALTER TABLE portfolio ADD CONSTRAINT freelancer FOREIGN KEY (freelancerId) REFERENCES freelancer (idFreelancer) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE portfolioitem CHANGE title title VARCHAR(255) DEFAULT NULL, CHANGE description description VARCHAR(500) DEFAULT NULL, CHANGE technologies technologies VARCHAR(500) DEFAULT NULL, CHANGE imageUrl imageUrl VARCHAR(500) DEFAULT NULL, CHANGE projectUrl projectUrl VARCHAR(255) DEFAULT NULL, CHANGE githubUrl githubUrl VARCHAR(255) DEFAULT NULL, CHANGE created_At created_At DATETIME NOT NULL');
        $this->addSql('ALTER TABLE portfolioitem ADD CONSTRAINT FK_D6C82F0B55073B34 FOREIGN KEY (idPortfolio) REFERENCES portfolio (idPortfolio) ON DELETE CASCADE');
        $this->addSql('DROP INDEX portfolio ON portfolioitem');
        $this->addSql('CREATE INDEX IDX_D6C82F0B55073B34 ON portfolioitem (idPortfolio)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON user (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE admin (idAdmin INT AUTO_INCREMENT NOT NULL, idUser INT NOT NULL, INDEX User (idUser), PRIMARY KEY(idAdmin)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE application (idApplication INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(idApplication)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE bank_account (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, account_holder_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, iban VARCHAR(34) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, bic VARCHAR(11) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, bank_name VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, is_default TINYINT(1) DEFAULT 0, date_added DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, date_modified DATETIME DEFAULT NULL, INDEX idx_is_default (is_default), INDEX idx_user_id (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'Table de gestion des comptes bancaires des utilisateurs. Les IBAN et BIC sont chiffrés en base.\' ');
        $this->addSql('CREATE TABLE chat (idChat INT AUTO_INCREMENT NOT NULL, created_At DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, status TINYINT(1) NOT NULL, projectID INT NOT NULL, userID INT NOT NULL, INDEX IdUser (userID), INDEX IdProject (projectID), PRIMARY KEY(idChat)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE contract (idContract INT AUTO_INCREMENT NOT NULL, Type VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT \'Standard\' COLLATE `utf8mb4_general_ci`, templateID INT DEFAULT 1, startDate DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, EndDate DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, status TINYINT(1) NOT NULL, amount DOUBLE PRECISION NOT NULL, projectID INT NOT NULL, PaymentID INT NOT NULL, ClientID INT NOT NULL, freelancerID INT DEFAULT NULL, clientSignatureDate DATETIME DEFAULT NULL, freelancerSignatureDate DATETIME DEFAULT NULL, clientSignatureImage LONGBLOB DEFAULT NULL, freelancerSignatureImage LONGBLOB DEFAULT NULL, INDEX idClient (ClientID), INDEX idPayment (PaymentID), INDEX id_project (projectID), PRIMARY KEY(idContract)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE contract_template (idTemplate INT AUTO_INCREMENT NOT NULL, templateName VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, templateContent TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, createdDate DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updatedDate DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY(idTemplate)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE contrat_type (idContratType INT AUTO_INCREMENT NOT NULL, typeName VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description VARCHAR(500) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, createdAt DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, UNIQUE INDEX typeName (typeName), PRIMARY KEY(idContratType)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE forum_private_message (message_id INT AUTO_INCREMENT NOT NULL, sender_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, recipient_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, content TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, is_read TINYINT(1) DEFAULT 0, PRIMARY KEY(message_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE message (idMessage INT AUTO_INCREMENT NOT NULL, content VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, sentDate DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, seen TINYINT(1) NOT NULL, ChatID INT NOT NULL, SenderID INT NOT NULL, INDEX idSender (SenderID), INDEX idChat (ChatID), PRIMARY KEY(idMessage)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE payment (idPayment INT AUTO_INCREMENT NOT NULL, amount DOUBLE PRECISION NOT NULL, datePayment DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, paymentStatus VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, userID INT NOT NULL, taskID INT DEFAULT NULL, INDEX id_task (taskID), INDEX id_user (userID), PRIMARY KEY(idPayment)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE payment_escrow (id INT AUTO_INCREMENT NOT NULL, contract_id INT NOT NULL, client_id INT NOT NULL, freelancer_id INT NOT NULL, amount NUMERIC(10, 2) NOT NULL, status VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT \'PENDING\' NOT NULL COLLATE `utf8mb4_general_ci` COMMENT \'PENDING, COMPLETED, RELEASED, REFUNDED\', date_creation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, date_completion DATETIME DEFAULT NULL COMMENT \'Quand le projet est livré\', date_liberation DATETIME DEFAULT NULL COMMENT \'Quand l\'\'admin libère le montant\', notes TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci` COMMENT \'Notes admin sur la validation\', INDEX idx_date_creation (date_creation), INDEX idx_client_id (client_id), INDEX idx_freelancer_id (freelancer_id), INDEX idx_status (status), INDEX idx_contract_id (contract_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'Table de gestion des paiements en escrow. Les montants sont bloqués jusqu\'\'à la validation de la livraison par l\'\'admin.\' ');
        $this->addSql('CREATE TABLE project (idProject INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, budget DOUBLE PRECISION NOT NULL, status TINYINT(1) NOT NULL, ClientID INT NOT NULL, freelancerID INT DEFAULT NULL, INDEX id_client (ClientID), PRIMARY KEY(idProject)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE task (idTask INT AUTO_INCREMENT NOT NULL, title INT NOT NULL, description INT NOT NULL, deadline DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, TaskStatus VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, dateAssign DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, role VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, priority INT NOT NULL, idProject INT NOT NULL, INDEX ProjectID (idProject), PRIMARY KEY(idTask)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE admin ADD CONSTRAINT User FOREIGN KEY (idUser) REFERENCES user (idUser) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bank_account ADD CONSTRAINT fk_bank_account_user FOREIGN KEY (user_id) REFERENCES user (idUser) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE chat ADD CONSTRAINT IdProject FOREIGN KEY (projectID) REFERENCES project (idProject) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE chat ADD CONSTRAINT IdUser FOREIGN KEY (userID) REFERENCES user (idUser) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contract ADD CONSTRAINT id_project FOREIGN KEY (projectID) REFERENCES project (idProject) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contract ADD CONSTRAINT idClient FOREIGN KEY (ClientID) REFERENCES client (idClient) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contract ADD CONSTRAINT idPayment FOREIGN KEY (PaymentID) REFERENCES payment (idPayment) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT idChat FOREIGN KEY (ChatID) REFERENCES chat (idChat) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT idSender FOREIGN KEY (SenderID) REFERENCES user (idUser) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT id_user FOREIGN KEY (userID) REFERENCES user (idUser) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE payment_escrow ADD CONSTRAINT fk_escrow_client FOREIGN KEY (client_id) REFERENCES client (idClient) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE payment_escrow ADD CONSTRAINT fk_escrow_contract FOREIGN KEY (contract_id) REFERENCES contract (idContract) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE payment_escrow ADD CONSTRAINT fk_escrow_freelancer FOREIGN KEY (freelancer_id) REFERENCES freelancer (idFreelancer) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C74404555FD86D04');
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C74404555FD86D04');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT UserID FOREIGN KEY (userID) REFERENCES user (idUser) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_c74404555fd86d04 ON client');
        $this->addSql('CREATE INDEX UserID ON client (userID)');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C74404555FD86D04 FOREIGN KEY (userID) REFERENCES `user` (idUser) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE freelancer DROP FOREIGN KEY FK_4C2ED1E8FE6E88D7');
        $this->addSql('ALTER TABLE freelancer DROP FOREIGN KEY FK_4C2ED1E8FE6E88D7');
        $this->addSql('ALTER TABLE freelancer ADD idApplication INT DEFAULT NULL, ADD idPortfolio INT DEFAULT NULL, CHANGE skills skills LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE verificationStatus verificationStatus VARCHAR(255) NOT NULL, CHANGE status status VARCHAR(255) NOT NULL');
        $this->addSql('CREATE INDEX ApplicationID ON freelancer (idApplication)');
        $this->addSql('CREATE INDEX TaskID ON freelancer (idTask)');
        $this->addSql('CREATE INDEX PortfolioID ON freelancer (idPortfolio)');
        $this->addSql('DROP INDEX idx_4c2ed1e8fe6e88d7 ON freelancer');
        $this->addSql('CREATE INDEX UserID ON freelancer (idUser)');
        $this->addSql('ALTER TABLE freelancer ADD CONSTRAINT FK_4C2ED1E8FE6E88D7 FOREIGN KEY (idUser) REFERENCES `user` (idUser) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE freelancer_forum_comment MODIFY id INT NOT NULL');
        $this->addSql('DROP INDEX `PRIMARY` ON freelancer_forum_comment');
        $this->addSql('ALTER TABLE freelancer_forum_comment ADD comment_text TEXT NOT NULL, DROP content, DROP author_name, CHANGE post_id post_id INT DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE id comment_id INT AUTO_INCREMENT NOT NULL, CHANGE author_id freelancer_id INT NOT NULL');
        $this->addSql('ALTER TABLE freelancer_forum_comment ADD CONSTRAINT freelancerID FOREIGN KEY (freelancer_id) REFERENCES freelancer (idFreelancer) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE freelancer_forum_comment ADD CONSTRAINT postID FOREIGN KEY (post_id) REFERENCES freelancer_forum_post (post_id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('CREATE INDEX freelancerID ON freelancer_forum_comment (freelancer_id)');
        $this->addSql('CREATE INDEX postID ON freelancer_forum_comment (post_id)');
        $this->addSql('ALTER TABLE freelancer_forum_comment ADD PRIMARY KEY (comment_id)');
        $this->addSql('ALTER TABLE freelancer_forum_post MODIFY id INT NOT NULL');
        $this->addSql('DROP INDEX `PRIMARY` ON freelancer_forum_post');
        $this->addSql('ALTER TABLE freelancer_forum_post ADD freelancer_id INT DEFAULT NULL, ADD updated_at DATETIME NOT NULL, DROP author_name, DROP author_id, CHANGE title title VARCHAR(200) DEFAULT NULL, CHANGE content content TEXT DEFAULT NULL, CHANGE category category VARCHAR(50) DEFAULT \'General\', CHANGE views views INT DEFAULT 0 NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE id post_id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE freelancer_forum_post ADD CONSTRAINT freelancerPOST FOREIGN KEY (freelancer_id) REFERENCES freelancer (idFreelancer) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('CREATE INDEX freelancerPOST ON freelancer_forum_post (freelancer_id)');
        $this->addSql('ALTER TABLE freelancer_forum_post ADD PRIMARY KEY (post_id)');
        $this->addSql('ALTER TABLE freelancer_forum_reaction MODIFY id INT NOT NULL');
        $this->addSql('DROP INDEX user_post_unique ON freelancer_forum_reaction');
        $this->addSql('DROP INDEX `PRIMARY` ON freelancer_forum_reaction');
        $this->addSql('ALTER TABLE freelancer_forum_reaction ADD reaction_type VARCHAR(20) DEFAULT \'LIKE\' NOT NULL, ADD created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, DROP type, CHANGE id reaction_id INT AUTO_INCREMENT NOT NULL, CHANGE user_id freelancer_id INT NOT NULL');
        $this->addSql('ALTER TABLE freelancer_forum_reaction ADD CONSTRAINT freelancerREACT FOREIGN KEY (freelancer_id) REFERENCES freelancer (idFreelancer) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE freelancer_forum_reaction ADD CONSTRAINT postREACT FOREIGN KEY (post_id) REFERENCES freelancer_forum_post (post_id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('CREATE INDEX postREACT ON freelancer_forum_reaction (post_id)');
        $this->addSql('CREATE INDEX freelancerREACT ON freelancer_forum_reaction (freelancer_id)');
        $this->addSql('ALTER TABLE freelancer_forum_reaction ADD PRIMARY KEY (reaction_id)');
        $this->addSql('ALTER TABLE portfolio DROP FOREIGN KEY FK_A9ED10624DCE947A');
        $this->addSql('ALTER TABLE portfolio DROP FOREIGN KEY FK_A9ED10624DCE947A');
        $this->addSql('ALTER TABLE portfolio CHANGE created_At created_At DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE portfolio ADD CONSTRAINT freelancer FOREIGN KEY (freelancerId) REFERENCES freelancer (idFreelancer) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_a9ed10624dce947a ON portfolio');
        $this->addSql('CREATE INDEX freelancer ON portfolio (freelancerId)');
        $this->addSql('ALTER TABLE portfolio ADD CONSTRAINT FK_A9ED10624DCE947A FOREIGN KEY (freelancerId) REFERENCES freelancer (idFreelancer) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE portfolioitem DROP FOREIGN KEY FK_D6C82F0B55073B34');
        $this->addSql('ALTER TABLE portfolioitem DROP FOREIGN KEY FK_D6C82F0B55073B34');
        $this->addSql('ALTER TABLE portfolioitem CHANGE title title VARCHAR(255) NOT NULL, CHANGE description description VARCHAR(255) NOT NULL, CHANGE technologies technologies JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE imageUrl imageUrl VARCHAR(255) NOT NULL, CHANGE projectUrl projectUrl VARCHAR(255) NOT NULL, CHANGE githubUrl githubUrl VARCHAR(255) NOT NULL, CHANGE created_At created_At DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('DROP INDEX idx_d6c82f0b55073b34 ON portfolioitem');
        $this->addSql('CREATE INDEX Portfolio ON portfolioitem (idPortfolio)');
        $this->addSql('ALTER TABLE portfolioitem ADD CONSTRAINT FK_D6C82F0B55073B34 FOREIGN KEY (idPortfolio) REFERENCES portfolio (idPortfolio) ON DELETE CASCADE');
        $this->addSql('DROP INDEX UNIQ_8D93D649E7927C74 ON `user`');
    }
}
