<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241221000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add gallery_image table and enhance existing entities for gallery functionality';
    }

    public function up(Schema $schema): void
    {
        // Create gallery_image table
        $this->addSql('CREATE TABLE gallery_image (
            uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', 
            event VARCHAR(255) NOT NULL, 
            title VARCHAR(255) DEFAULT NULL, 
            description LONGTEXT DEFAULT NULL, 
            image_name VARCHAR(255) DEFAULT NULL, 
            updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', 
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', 
            INDEX IDX_21A0D47C3BAE0AA7 (event), 
            PRIMARY KEY(uuid)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Add checkoutId to shop_order if it doesn't exist and table exists
        $tableExists = $this->connection->getSchemaManager()->tablesExist(['shop_order']);
        if ($tableExists) {
            $this->addSql('ALTER TABLE shop_order ADD checkout_id VARCHAR(255) DEFAULT NULL');
            $this->addSql('CREATE INDEX IDX_323FC9CA4C7C611F ON shop_order (checkout_id)');
        }

        // Add settings for prepage functionality if settings table exists
        $settingTableExists = $this->connection->getSchemaManager()->tablesExist(['setting']);
        if ($settingTableExists) {
            $this->addSql('INSERT IGNORE INTO setting (name, value, created_at, updated_at) VALUES 
                ("site.prepage.show", "false", NOW(), NOW()),
                ("site.prepage.text", "", NOW(), NOW())');
        }
    }

    public function down(Schema $schema): void
    {
        // Drop gallery_image table
        $this->addSql('DROP TABLE IF EXISTS gallery_image');

        // Remove checkoutId from shop_order if it exists
        $tableExists = $this->connection->getSchemaManager()->tablesExist(['shop_order']);
        if ($tableExists) {
            $this->addSql('DROP INDEX IDX_323FC9CA4C7C611F ON shop_order');
            $this->addSql('ALTER TABLE shop_order DROP COLUMN checkout_id');
        }

        // Remove prepage settings if table exists
        $settingTableExists = $this->connection->getSchemaManager()->tablesExist(['setting']);
        if ($settingTableExists) {
            $this->addSql('DELETE FROM setting WHERE name IN ("site.prepage.show", "site.prepage.text")');
        }
    }
}