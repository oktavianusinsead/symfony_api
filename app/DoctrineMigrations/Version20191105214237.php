<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191105214237 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        if($schema->hasTable('user_profiles_cache')) {
            if (!$schema->getTable('user_profiles_cache')->hasColumn('country_code')) {
                $this->addSql("ALTER TABLE user_profiles_cache ADD country_code VARCHAR(255) DEFAULT NULL;");
            }
        }

        if($schema->hasTable('user_profiles')) {
            if (!$schema->getTable('user_profiles')->hasColumn('country_code')) {
                $this->addSql("ALTER TABLE user_profiles ADD country_code VARCHAR(255) DEFAULT NULL;");
            }
        }
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
    }
}
