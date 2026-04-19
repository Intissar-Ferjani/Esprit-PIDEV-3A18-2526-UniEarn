<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260419000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Recreate chat+message tables for messaging, add notification table, add user.last_active_at';
    }

    public function up(Schema $schema): void
    {
        // Drop old tables (no data, old schema)
        $this->addSql('SET FOREIGN_KEY_CHECKS=0');
        $this->addSql('DROP TABLE IF EXISTS message');
        $this->addSql('DROP TABLE IF EXISTS chat');
        $this->addSql('DROP TABLE IF EXISTS notification');
        $this->addSql('SET FOREIGN_KEY_CHECKS=1');

        // last_active_at already added by previous partial run — skip
        $this->addSql('
            CREATE TABLE chat (
                idChat INT AUTO_INCREMENT NOT NULL,
                freelancer1_id INT NOT NULL,
                freelancer2_id INT NOT NULL,
                created_at DATETIME NOT NULL,
                last_message_at DATETIME NOT NULL,
                PRIMARY KEY (idChat),
                UNIQUE INDEX uk_conversation (freelancer1_id, freelancer2_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');

        $this->addSql('
            CREATE TABLE message (
                idMessage INT AUTO_INCREMENT NOT NULL,
                content VARCHAR(255) NOT NULL,
                sentDate DATETIME NOT NULL,
                seen TINYINT(1) NOT NULL DEFAULT 0,
                ChatID INT NOT NULL,
                SenderID INT NOT NULL,
                PRIMARY KEY (idMessage),
                INDEX idx_chat (ChatID),
                INDEX idx_sender (SenderID),
                CONSTRAINT fk_msg_chat   FOREIGN KEY (ChatID)   REFERENCES chat   (idChat)  ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT fk_msg_sender FOREIGN KEY (SenderID) REFERENCES `user` (idUser)  ON DELETE CASCADE ON UPDATE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');

        $this->addSql('
            CREATE TABLE notification (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT NOT NULL,
                type VARCHAR(30) NOT NULL,
                content TEXT NOT NULL,
                link VARCHAR(255) DEFAULT NULL,
                is_read TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                INDEX idx_notif_user (user_id),
                CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES `user` (idUser) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS=0');
        $this->addSql('DROP TABLE IF EXISTS message');
        $this->addSql('DROP TABLE IF EXISTS chat');
        $this->addSql('DROP TABLE IF EXISTS notification');
        $this->addSql('SET FOREIGN_KEY_CHECKS=1');
        $this->addSql('ALTER TABLE `user` DROP COLUMN last_active_at');
    }
}
