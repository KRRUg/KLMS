<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251107090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create news_comment table for user comments on news entries.';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('news_comment');
        $table->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $table->addColumn('news_id', Types::INTEGER, []);
        $table->addColumn('content', Types::TEXT, []);
        $table->addColumn('author_id', Types::GUID, []);
        $table->addColumn('modifier_id', Types::GUID, []);
        $table->addColumn('last_modified', Types::DATETIME_MUTABLE, []);
        $table->addColumn('created', Types::DATETIME_MUTABLE, []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['news_id'], 'IDX_NEWS_COMMENT_NEWS_ID');
        $table->addForeignKeyConstraint('news', ['news_id'], ['id'], ['onDelete' => 'CASCADE']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('news_comment');
    }
}
