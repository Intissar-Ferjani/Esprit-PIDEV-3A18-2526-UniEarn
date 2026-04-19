<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260419000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add gif_url column to freelancer_forum_comment';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE freelancer_forum_comment ADD gif_url VARCHAR(500) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE freelancer_forum_comment DROP COLUMN gif_url');
    }
}
