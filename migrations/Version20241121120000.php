<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241121120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add group stage support for tourneys, teams, and games.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tourney ADD group_count INT DEFAULT NULL, ADD group_advance INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tourney_team ADD group_key VARCHAR(8) DEFAULT NULL');
        $this->addSql('ALTER TABLE tourney_game ADD is_group_stage TINYINT(1) NOT NULL DEFAULT 0, ADD group_key VARCHAR(16) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tourney DROP group_count, DROP group_advance');
        $this->addSql('ALTER TABLE tourney_team DROP group_key');
        $this->addSql('ALTER TABLE tourney_game DROP is_group_stage, DROP group_key');
    }
}
