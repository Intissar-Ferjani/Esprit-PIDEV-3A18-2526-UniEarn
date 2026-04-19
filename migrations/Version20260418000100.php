<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260418000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add task review submission fields.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE task ADD submission_link VARCHAR(255) DEFAULT NULL, ADD submission_file VARCHAR(255) DEFAULT NULL, ADD client_feedback LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE task DROP submission_link, DROP submission_file, DROP client_feedback');
    }
}
